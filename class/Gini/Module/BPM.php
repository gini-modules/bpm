<?php

namespace Gini\Module;

class BPM
{
    public static function setup()
    {
    }

    public static function diagnose()
    {
        $error = [];
        $conf = \Gini\Config::get('app.order_review_process');
        if (empty($conf)) {
            $error[] = 'please set value for app.order_review_process in yml';
        }

        $conf = \Gini\Config::get('bpm-process-engine.default');
        if (empty($conf)) {
            $error[] = 'please set value for bpm-process-engine.default in yml';
        }

        return $error;
    }
}
