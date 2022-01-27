<?php

namespace MyFatoorah\Payment\Cron;

class BackupLogFiles {

    protected $log;

//---------------------------------------------------------------------------------------------------------------------------------------------------
    public function createNewLogFile() {

        $log = new \Zend\Log\Logger();
        $log->addWriter(new \Zend\Log\Writer\Stream(BP . '/var/log/myfatoorah.log'));

        $log->info('In Cron Job: BackupLogFiles');

        $logPath = BP . '/var/log';
        $logFile = "$logPath/myfatoorah.log";

        if (file_exists($logFile)) {

            $mfOldLog = "$logPath/mfOldLog";
            if (!file_exists($mfOldLog)) {
                mkdir($mfOldLog);
            }
            rename($logFile, "$mfOldLog/myfatoorah_" . date('Y-m-d') . '.log');
        }
        return true;
    }

//---------------------------------------------------------------------------------------------------------------------------------------------------
}
