<?php declare(strict_types=1);

namespace KskEcbCurrency\Models;

use Doctrine\ORM\EntityRepository;
use KskEcbCurrency\Exceptions\SuccessfulUpdateReportsNotFoundException;

class UpdateReportRepository extends EntityRepository
{
    /**
     * @throws SuccessfulUpdateReportsNotFoundException
     *
     * @return UpdateReport
     */
    public function getLatestSuccessfulUpdateReport()
    {
        $builder = $this->createQueryBuilder('update_report');
        $builder->andWhere('update_report.success = 1')
            ->addOrderBy('update_report.timestamp', 'DESC')
            ->setMaxResults(1);

        if (empty($report = $builder->getQuery()->getResult())) {
            throw new SuccessfulUpdateReportsNotFoundException();
        }

        return array_shift($report);
    }
}
