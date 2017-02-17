<?php
/**
 * @package: chapi
 *
 * @author:  msiebeneicher
 * @since:   2015-07-30
 *
 */

namespace Chapi\Commands;

use Chapi\Service\JobRepository\JobRepositoryInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;

class InfoCommand extends AbstractCommand
{
    /**
     * Configures the current command.
     */
    protected function configure()
    {
        $this->setName('info')
            ->setDescription('Display your job information from chronos')
            ->addArgument('jobName', InputArgument::REQUIRED, 'selected job')
        ;
    }

    /**
     * @return int
     */
    protected function process()
    {
        $_sJobName = $this->oInput->getArgument('jobName');

        $_oChronosJobEntity = $this->checkInChronos($_sJobName);
        $_oJobEntity = $_oChronosJobEntity == null ? $this->checkInMarathon($_sJobName) : $_oChronosJobEntity;

        if (!$_oJobEntity)
        {
            $this->oOutput->writeln(sprintf('<fg=red>%s</>', 'Could not find the job.'));
            return 1;
        }

        $this->oOutput->writeln(sprintf("\n<comment>info '%s'</comment>\n", $_oJobEntity->getKey()));

        $_oTable = new Table($this->oOutput);
        $_oTable->setHeaders(array('Property', 'Value'));

        foreach ($_oJobEntity as $_sKey => $_mValue)
        {
            if (is_array($_mValue) || is_object($_mValue))
            {
                $_sEmptyString = (is_object($_mValue)) ? '{ }' : '[ ]';

                $_mValue = (!empty($_mValue))
                    ? json_encode($_mValue, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
                    : $_sEmptyString;
            }
            elseif (is_bool($_mValue))
            {
                $_mValue = (true === $_mValue)
                    ? 'true'
                    : 'false';
            }

            $_oTable->addRow(array($_sKey, $_mValue));
        }

        $_oTable->render();

        return 0;
    }

    private function checkInChronos($sJobName)
    {
        /** @var JobRepositoryInterface  $_oJobRepositoryChronos */
        $_oJobRepositoryChronos = $this->getContainer()->get(JobRepositoryInterface::DIC_NAME_CHRONOS);
        return $_oJobRepositoryChronos->getJob($sJobName);
    }

    private function checkInMarathon($sJobName)
    {
        /** @var JobRepositoryInterface  $_oJobRepositoryMarathon */
        $_oJobRepositoryMarathon = $this->getContainer()->get(JobRepositoryInterface::DIC_NAME_MARATHON);
        return $_oJobRepositoryMarathon->getJob($sJobName);
    }
}