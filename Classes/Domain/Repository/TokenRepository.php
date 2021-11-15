<?php

/**
 * (c) Kitodo. Key to digital objects e.V. <contact@kitodo.org>
 *
 * This file is part of the Kitodo and TYPO3 projects.
 *
 * @license GNU General Public License version 3 or later.
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace Kitodo\Dlf\Domain\Repository;

class TokenRepository extends \TYPO3\CMS\Extbase\Persistence\Repository
{
    public function deleteExpiredTokens($execTime, $expired) {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tx_dlf_tokens');

        $result = $queryBuilder
            ->delete('tx_dlf_tokens')
            ->where(
                $queryBuilder->expr()->eq('tx_dlf_tokens.ident', $queryBuilder->createNamedParameter('oai')),
                $queryBuilder->expr()->lt('tx_dlf_tokens.tstamp',
                    $queryBuilder->createNamedParameter((int) ($execTime - $expired)))
            )
            ->execute();

        return $result;
    }

    public function getResumptionToken($token) {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_dlf_tokens');

        // Get resumption token.
        $result = $queryBuilder
            ->select('tx_dlf_tokens.options AS options')
            ->from('tx_dlf_tokens')
            ->where(
                $queryBuilder->expr()->eq('tx_dlf_tokens.ident', $queryBuilder->createNamedParameter('oai')),
                $queryBuilder->expr()->eq('tx_dlf_tokens.token',
                    $queryBuilder->expr()->literal($token)
                )
            )
            ->setMaxResults(1)
            ->execute();

        return $result;
    }

    public function generateResumptionToken($token, $documentListSet) {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tx_dlf_tokens');
        $affectedRows = $queryBuilder
            ->insert('tx_dlf_tokens')
            ->values([
                'tstamp' => $GLOBALS['EXEC_TIME'],
                'token' => $token,
                'options' => serialize($documentListSet),
                'ident' => 'oai',
            ])
            ->execute();

        return $affectedRows;
    }

}