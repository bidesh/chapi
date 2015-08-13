<?php
/**
 * @package: chapi
 *
 * @author:  msiebeneicher
 * @since:   2015-08-13
 */


namespace ChapiTest\unit\BusinessCase\Comparison;


use Chapi\BusinessCase\Comparison\JobComparisonBusinessCase;
use Chapi\Component\DatePeriod\DatePeriodFactory;
use ChapiTest\src\TestTraits\JobEntityTrait;

class JobComparisonBusinessCaseTest extends \PHPUnit_Framework_TestCase
{
    use JobEntityTrait;

    /** @var \Prophecy\Prophecy\ObjectProphecy */
    private $oJobRepositoryLocal;

    /** @var \Prophecy\Prophecy\ObjectProphecy */
    private $oJobRepositoryChronos;

    /** @var \Prophecy\Prophecy\ObjectProphecy */
    private $oDiffCompare;

    /** @var DatePeriodFactory */
    private $oDatePeriodFactory;

    public function setUp()
    {
        $this->oJobRepositoryLocal = $this->prophesize('Chapi\Service\JobRepository\JobRepositoryServiceInterface');
        $this->oJobRepositoryChronos = $this->prophesize('Chapi\Service\JobRepository\JobRepositoryServiceInterface');
        $this->oDiffCompare = $this->prophesize('Chapi\Component\Comparison\DiffCompareInterface');

        $this->oDatePeriodFactory = new DatePeriodFactory();


    }

    public function testGetLocalJobUpdatesSuccess()
    {
        $_JobEntityA1 = $this->getValidScheduledJobEntity();
        $_JobEntityA2 = $this->getValidScheduledJobEntity();
        $_JobEntityA2->scheduleTimeZone = 'Europe/London';

        $_JobEntityB1 = $this->getValidScheduledJobEntity('JobB');
        $_JobEntityB2 = $this->getValidScheduledJobEntity('JobB');
        $_JobEntityB2->schedule = 'R0/2015-09-01T02:00:00Z/P1D';

        $_JobEntityC1 = $this->getValidScheduledJobEntity('JobC');
        $_JobEntityC2 = $this->getValidScheduledJobEntity('JobC');

        $_JobEntityD1 = $this->getValidScheduledJobEntity('JobD');
        $_JobEntityD2 = $this->getValidScheduledJobEntity('JobD');
        $_iTime1 = strtotime('-1 day -2 hours');
        $_iTime2 = strtotime('+45 min');
        $_JobEntityD1->schedule = sprintf('R/%d-%s-%sT%s:00:00Z/PT1H', date('Y', $_iTime1), date('m', $_iTime1), date('d', $_iTime1), date('H', $_iTime1)); // 'R/2015-01-01T02:00:00Z/PT1H';
        $_JobEntityD2->schedule = sprintf('R/%d-%s-%sT%s:00:00Z/PT1H', date('Y', $_iTime2), date('m', $_iTime2), date('d', $_iTime2), date('H', $_iTime2)); // 'R/2015-01-01T02:00:00Z/PT1H';


        $_JobEntityE1 = $this->getValidScheduledJobEntity('JobE');
        $_JobEntityE2 = $this->getValidScheduledJobEntity('JobE');
        $_sDate1 = date('Y-m-d', strtotime('-7 day'));
        $_sDate2 = date('Y-m-d', strtotime('-3 day'));
        $_JobEntityE1->schedule = 'R/' . $_sDate1 . 'T10:35:00Z/PT1M';
        $_JobEntityE2->scheduleTimeZone = 'Europe/Berlin';
        $_JobEntityE2->schedule = 'R/' . $_sDate2 . 'T13:14:00.000+02:00/PT1M';
        $_JobEntityE2->scheduleTimeZone = '';

        $_aJobCollection = [
            $_JobEntityA1,
            $_JobEntityB1,
            $_JobEntityC1,
            $_JobEntityD1,
            $_JobEntityE1
        ];

        $this->oJobRepositoryLocal
            ->getJobs()
            ->shouldBeCalledTimes(1)
            ->willReturn($_aJobCollection);

        $this->oJobRepositoryChronos
            ->getJob($_JobEntityA1->name)
            ->shouldBeCalledTimes(1)
            ->willReturn($_JobEntityA2);

        $this->oJobRepositoryChronos
            ->getJob($_JobEntityB1->name)
            ->shouldBeCalledTimes(1)
            ->willReturn($_JobEntityB2);

        $this->oJobRepositoryChronos
            ->getJob($_JobEntityC1->name)
            ->shouldBeCalledTimes(1)
            ->willReturn($_JobEntityC2);

        $this->oJobRepositoryChronos
            ->getJob($_JobEntityD1->name)
            ->shouldBeCalledTimes(1)
            ->willReturn($_JobEntityD2);

        $this->oJobRepositoryChronos
            ->getJob($_JobEntityE1->name)
            ->shouldBeCalledTimes(1)
            ->willReturn($_JobEntityE2);

        $_oJobComparisonBusinessCase = new JobComparisonBusinessCase(
            $this->oJobRepositoryLocal->reveal(),
            $this->oJobRepositoryChronos->reveal(),
            $this->oDiffCompare->reveal(),
            $this->oDatePeriodFactory
        );

        $_aLocalJobUpdates = $_oJobComparisonBusinessCase->getLocalJobUpdates();

        $this->assertEquals(
            ['JobA', 'JobB'],
            $_aLocalJobUpdates
        );
    }
}