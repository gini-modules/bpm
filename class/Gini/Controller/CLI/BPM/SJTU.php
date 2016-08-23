<?php

namespace Gini\Controller\CLI\BPM;

class SJTU extends \Gini\Controller\CLI
{
    public function actionAddProcess()
    {
        $name = readline('please input name([a-z_\-]+): ');
        if (!$name) return;
        $file = dirname(__FILE__).'/rules.json';
        $rules = json_decode(file_get_contents($file));
        $process = a('sjtu/bpm/process', [
            'name'=> $name
        ]);
        $process->name = $name;
        $process->ctime = date('Y-m-d H:i:s');
        $process->rules = $rules;
        $process->save();
        echo "process#{$process->id}\n";
    }

    public function actionAddGroup()
    {
        $name = readline('please input process name([a-z_\-]+): ');
        if (!$name) return;
        $process = those('sjtu/bpm/process')->whose('name')->is($name)
            ->orderBy('version', 'asc')->current();
        if (!$process->id) {
            echo "process#{$name} not exists\n";
            return;
        }

        $name = readline('please input group name([a-z_\-]+): ');
        if (!$name) return;

        $title = readline('please input group title: ');
        $title = $title ?: $name;
        $description = readline('please input group description: ');

        $group = a('sjtu/bpm/process/group');
        $group->process = $process;
        $group->name = $name;
        $group->title = $title;
        $group->description = $description;

        $group->save();

        echo "group#{$group->id}#{$group->name}\n";
    }

    public function actionExpandGroups()
    {
        $name = readline('please input process name([a-z_\-]+): ');
        if (!$name) return;
        $process = those('sjtu/bpm/process')->whose('name')->is($name)
            ->orderBy('version', 'asc')->current();
        if (!$process->id) {
            echo "process#{$name} not exists\n";
            return;
        }
        $rules = $process->rules;
        foreach ($rules as $key=>$rule) {
            if (!isset($rule['group'])) {
                continue;
            }
            $name = $rule['group'];
            $title = $rule['group-title'] ?: $name;
            $description = $rule['group-description'] ?: $title;
            $group = a('sjtu/bpm/process/group', ['name'=> $name]);
            if ($group->id) continue;
            $group->process = $process;
            $group->name = $name;
            $group->title = $title;
            $group->description = $description;
            $group->save();
        }
    }

    public function actionTest()
    {
        $processName = 'order-review-process';
        $engine = \Gini\Process\Engine::of('default');
        // return $engine->startProcessInstance($processName, ['data'=>[
        //     'id'=> 1,
        //     'voucher'=> 'M201608020001',
        //     'price'=> round('1000', 2),
        //     'ctime'=> date('Y-m-d H:i:s'),
        //     'status'=> 1,
        //     'group_id'=> 1,
        //     'vendor_id'=> 1,
        //     'user_id'=> 1,
        //     'items'=> [
        //          [
        //              'cas_no'=> '12-20-1'
        //          ]
        //     ]
        // ]]);
        $instance = $engine->fetchProcessInstance($processName, 1);
        $instance->start();
        $instance->next();
/*
        $tasks = $engine->those('task')->whose('process')->is('xxx')->whose('candidate_gro
    5.        $task = $engine->getTask($task_id);
              $task->claim($me->id);
              $task->complete();
              $task->update([
                  'status' => 'xxx'
              ]);
*/
    }
}
