<?php

use Sil\SspUtils\AnnouncementUtils;
use Sil\SspUtils\DiscoUtils;
use Sil\SspUtils\Metadata;

/**
 * This class implements a custom IdP discovery service, for use with a ssp hub (proxy)
 *
 * This module extends the basic IdP disco handler.
 *
 * @author Steve Bagwell SIL GTIS
 * @package SimpleSAMLphp
 */
class sspmod_sildisco_IdPDisco extends SimpleSAML_XHTML_IdPDisco
{

    /* The session type for this class */
    public static $sessionType = 'sildisco:authentication';

    /* The session key for checking if the current user has the beta_tester cookie */
    public static $betaTesterSessionKey = 'beta_tester';

    /* The idp metadata key that says whether an IDP is betaEnabled */
    public static $betaEnabledMdKey = 'betaEnabled';

    /* The idp metadata key that says whether an IDP is enabled */
    public static $enabledMdKey = 'enabled';

    /* The sp metadata key that gives the name of the SP */
    public static $spNameMdKey = 'name';

    /**
     * Log a message.
     *
     * This is an helper function for logging messages. It will prefix the messages with our discovery service type.
     *
     * @param string $message The message which should be logged.
     */
    protected function log($message)
    {
        SimpleSAML\Logger::info('SildiscoIdPDisco.'.$this->instance.': '.$message);
    }

    /**
     * Handles a request to this discovery service.
     *
     * The IdP disco parameters should be set before calling this function.
     */
    public function handleRequest()
    {
        $this->start();

        // no choice made. Show discovery service page
        $idpList = $this->getIdPList();
        $idpList = $this->filterList($idpList);

        $metadataPath = __DIR__ . '/../../../metadata/';

        $sessionDataType = 'sildisco:authentication';
        $sessionKey = 'spentityid';
        $spEntityId = $this->session->getData($sessionDataType, $sessionKey);

        $idpList = DiscoUtils::getReducedIdpList(
            $idpList,
            $metadataPath,
            $spEntityId
        );

        $idpList = self::enableBetaEnabled($idpList);

        if (sizeof($idpList) == 1) {
            $this->log(
                'Choice made [' . array_keys($idpList)[0] . '] (Redirecting the user back. returnIDParam='.
                $this->returnIdParam.')'
            );

            \SimpleSAML\Utils\HTTP::redirectTrustedURL(
                $this->returnURL,
                array($this->returnIdParam => array_keys($idpList)[0])
            );
        }

        // Get the SP's name
        $spEntries = Metadata::getSpMetadataEntries($metadataPath);
        $spName = $spEntries[$spEntityId][self::$spNameMdKey] ?? null;

        $templateFileName = 'selectidp-' . $this->config->getString('idpdisco.layout', 'links') . '.php';

        $t = new SimpleSAML_XHTML_Template($this->config, $templateFileName, 'disco');
        $t->data['idplist'] = $idpList;
        $t->data['return'] = $this->returnURL;
        $t->data['returnIDParam'] = $this->returnIdParam;
        $t->data['entityID'] = $this->spEntityId;
        $t->data['spName'] = $spName;
        $t->data['urlpattern'] = htmlspecialchars(\SimpleSAML\Utils\HTTP::getSelfURLNoQuery());
        $t->data['announcement'] = AnnouncementUtils::getSimpleAnnouncement();

        $t->show();
    }

    /**
     * @param array $idpList the IDPs with their metadata
     * @param bool $isBetaTester optional (default=null) just for unit testing
     * @return array $idpList
     *
     * If the current user has the beta_tester cookie, then for each IDP in
     * the idpList that has 'betaEnabled' => true, give it 'enabled' => true
     *
     */
    public static function enableBetaEnabled($idpList, $isBetaTester=null) {

        if ( $isBetaTester === null) {
            $session = SimpleSAML_Session::getSessionFromRequest();
            $isBetaTester = $session->getData(
                self::$sessionType,
                self::$betaTesterSessionKey
            );
        }

        if ( ! $isBetaTester) {
            return $idpList;
        }

        foreach ($idpList as $idp => $idpMetadata) {
            if ( ! empty($idpMetadata[self::$betaEnabledMdKey])) {
                $idpMetadata[self::$enabledMdKey] = true;
                $idpList[$idp] = $idpMetadata;
            }
        }

        return $idpList;
    }
}
