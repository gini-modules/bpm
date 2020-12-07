<?php

namespace Gini\ORM\SJTU\BPM;

// 流程定义
class Process extends \Gini\ORM\Object
{
    public $name = 'string:120';
    public $parent = 'object:sjtu/bpm/process';
    public $version = 'int,default:0';
    public $ctime = 'datetime';
    public $rules = 'array';

    public function getNextTaskInfo($task=null)
    {
        $rules = (array) $this->rules;
        if (!$task || !$task->id) {
            return [key($rules), current($rules)];
        }
        $position = $task->position;
        $rule = $rules[$position];
        if ($task->auto_callback) {
            if (!is_null($task->auto_callback_value)) {
                $switch = $rule['switch'];
                $pregSwitch = $rule['switch-preg'];
                if (isset($switch[$task->auto_callback_value])) {
                    $position = $switch[$task->auto_callback_value];
                    return [$position, $rules[$position]];
                }
                if (!empty($pregSwitch)) {
                    foreach ($pregSwitch as $pattern=>$pos) {
                        if (preg_match($pattern, $task->auto_callback_value)) {
                            $tmpRule = (array) $rules[$pos];
                            $tmpRule['group'] = $task->auto_callback_value;
                            return [$pos, $tmpRule];
                        }
                    }
                }
            }
        } else {
            if ($task->status==\Gini\ORM\SJTU\BPM\Process\Task::STATUS_APPROVED) {
                $position = $rule['approved'];
                if (isset($rules[$position])) {
                    return [$position, $rules[$position]];
                }
            }
            else if ($task->status==\Gini\ORM\SJTU\BPM\Process\Task::STATUS_UNAPPROVED) {
                $position = $rule['unapproved'];
                if (isset($rules[$position])) {
                    return [$position, $rules[$position]];
                }
            }
        }
    }

    public function getGroups($user=null)
    {
        $result = those('sjtu/bpm/process/group')->whose('process')->is($this);
        if (!is_null($user)) {
            $gus = those('sjtu/bpm/process/group/user')->whose('user')->is($user);
            $gids = [];
            foreach ($gus as $gu) {
                $gids[] = $gu->group->id;
            }
            $result = those('sjtu/bpm/process/group')->whose('process')->is($this)
                    ->whose('id')->isIn($gids);
        }
        return $result;
    }

    public function getGroup($groupName)
    {
        $group = a('sjtu/bpm/process/group', [
            'process'=> $this,
            'name'=> $groupName
        ]);

        return $group->id ? $group : null;
    }

    public function addGroup($groupName, $data)
    {
        $group = a('sjtu/bpm/process/group');
        $group->process = $this;
        $group->name = $groupName;
        $group->title = $data['title'];
        $group->description = $data['description'];

        return !!$group->save();
    }

    public function removeGroup($groupName)
    {
        $group = a('sjtu/bpm/process/group', ['name'=>$groupName, 'process'=>$this]);
        if (!$group->id) return false;
        $db = \Gini\Database::db();
        $db->beginTransaction();
        try {
            $sql = "DELETE FROM sjtu_bpm_process_group_user WHERE group_id={$group->id}";
            if (!$db->query($sql)) {
                throw new \Exception();
            }
            if (!$group->delete()) {
                throw new \Exception();
            }
            $db->commit();
        } catch (\Exception $e) {
            $db->rollback();
            return false;
        }
        return true;
    }

    public function updateGroup($groupName, $data)
    {
        $group = a('sjtu/bpm/process/group', ['name'=>$groupName, 'process'=>$this]);
        if (!$group->id) return false;
        $group->title = $data['title'];
        $group->description = $data['description'];
        return !!$group->save();
    }

    public function getInstances($start=0, $perpage=25, $user=null)
    {
        $sql = "SELECT DISTINCT instance_id AS id FROM sjtu_bpm_process_task WHERE process_id={$this->id}";

        if (!is_null($user)) {
            $groups = $this->getGroups($user);
            $gids = [];
            foreach ($groups as $group) {
                $gids[] = $group->id;
            }
            if (empty($gids)) return;
            $gids = implode(',', $gids);
            $sql = "{$sql} AND candidate_group_id IN ({$gids})";
        }

        $sql = "{$sql} ORDER BY id DESC LIMIT {$start},{$perpage}";
        $db = \Gini\Database::db();
        $query = $db->query($sql);
        $instances = [];
        if ($query) foreach ($query->rows() as $obj) {
            $instances[$obj->id] = a('sjtu/bpm/process/instance', $obj->id);
        }

        return $instances;
    }

    public function searchInstances($user=null)
    {
        $sql = "SELECT count(DISTINCT instance_id) FROM sjtu_bpm_process_task WHERE process_id={$this->id}";

        if (!is_null($user)) {
            $groups = $this->getGroups($user);
            $gids = [];
            foreach ($groups as $group) {
                $gids[] = $group->id;
            }
            if (empty($gids)) return;
            $gids = implode(',', $gids);
            $sql = "{$sql} AND candidate_group_id IN ({$gids})";
        }

        $db = \Gini\Database::db();
        $query = $db->query($sql);
        if (!$query) return;

        return $query->value();
    }

    // 易制毒审批记录查询
    public function drugInstances($start=0, $perpage=25, $user=null, $params=[], $all=false)
    {
        $db = \Gini\Database::db();
        $sql = "SELECT distinct i.id FROM sjtu_bpm_process_instance i LEFT JOIN plan p ON SUBSTRING_INDEX(i.tag,'#',-1) =  p.id  LEFT JOIN sjtu_bpm_process_task t ON i.id = t.instance_id WHERE ";
        $countSQL = "SELECT count(distinct i.id) FROM sjtu_bpm_process_instance i LEFT JOIN plan p ON SUBSTRING_INDEX(i.tag,'#',-1) =  p.id  LEFT JOIN sjtu_bpm_process_task t ON i.id = t.instance_id WHERE ";
        $where = ["i.process_id = {$db->quote($this->id)}"];

        $enableGids = [];
        if (!is_null($user)) {
            $groups = $this->getGroups($user);
            foreach ($groups as $group) {
                $enableGids[] = $group->id;
            }
            if (empty($enableGids)) return;
            $where[] = "t.candidate_group_id in ({$db->quote($enableGids)})";
        }

        //指定课题组
        if (@!empty($params['group'])) {
            $group = $db->query("select id from gapper_agent_group where title = {$db->quote($params['group'])}")->value();
            if (!$group) return false;
            $where[] = "p.group_id = {$db->quote($group)}";
        }


        if (@!empty($params['requester'])) {
            $where[] = "p.requester_name = {$db->quote($params['requester'])}";
        }

        if (@!empty($params['owner'])) {
            $where[] = "p.group_owner = {$db->quote($params['owner'])}";
        }

        if (@!empty($params['chem_name'])) {
            $where[] = "p.q_cache_chem_name like {$db->quote('%'.$params['chem_name'].'%')}";
        }

        if (@!empty($params['vendor'])) {
            $where[] = "p.q_cache_vendor_name like {$db->quote('%'.$params['vendor'].'%')}";
        }

        if (@!empty($params['status'])) {
            $status = ["1"=>"-1","2"=>0][$params['status']];
            $where[] = "i.status = {$db->quote($status)}";
        }
        if (@!empty($params['stime']) && @!empty($params['etime'])) $where[] = "p.date > {$db->quote($params['stime'])} AND p.date < {$db->quote($params['etime'])}";
        if (@!empty($params['school'])) $where[] = "p.school_name = {$db->quote($params['school'])}";

        $sql = $sql . implode(" AND ", $where) ." ORDER BY i.id DESC";
        if (!$all) {
            $sql = $sql . " LIMIT {$start},{$perpage}";
        }
        $countSQL = $countSQL . implode(" AND ", $where);
        $total = $db->query($countSQL)->value();
        if (!$total) return;

        $query = $db->query($sql);
        $instances = [];
        if ($query) foreach ($query->rows() as $obj) {
            $instances[$obj->id] = a('sjtu/bpm/process/instance', $obj->id);
        }

        return [$total, $instances];
    }
}

