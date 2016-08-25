<?php

namespace Gini\Controller\CLI\BPM;

class Robot extends \Gini\Controller\CLI
{
    public function actionWork()
    {
        $pidFile = APP_PATH.'/'.DATA_DIR.'/bpm.cli.robot.work.pid';
        if (file_exists($pidFile)) {
            $rawPID = (int) file_get_contents($pidFile);
            if ($rawPID) {
                if (file_exists("/proc/{$rawPID}")) return;
            }
        }
        $pid = getmypid();
        file_put_contents($pidFile, $pid);

        $engine = \Gini\Process\Engine::of('default');
        $date = date('Y-m-d H:i:s', strtotime('-5 minutes'));
        $start = 0;
        $perpage = 10;
        while (true) {
            $instances = $engine->those('instance')->whose('status')->isNot(\Gini\Process\IInstance::STATUS_END)
                ->andWhose('last_run_time')->isLessThan($date)
                ->limit($start, $perpage);
            if (!count($instances)) {
                break;
            }
            $start += $perpage;
            foreach ($instances as $instance) {
                $instance->last_run_time = date('Y-m-d H:i:s');
                $instance->save();
                $instance->next();
            }
        }
    }
}
