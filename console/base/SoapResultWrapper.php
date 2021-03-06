<?php
namespace solbianca\fias\console\base;

/**
 * Class SoapResultWrapper
 * @package solbianca\fias\console\base
 *
 * Обертка над апи сайта fias
 */
class SoapResultWrapper
{
    private $versionId;
    private $updateFileUrl;
    private $initFileUrl;

    public function __construct(\stdClass $rawResult)
    {
        // сюда может быть передан уже готовый объект DownloadFileInfo (когда идем по списку версий)
        if (!isset($rawResult->VersionId)) {
            $rawResult = $rawResult->GetLastDownloadFileInfoResult;
        }
        $this->versionId = $rawResult->VersionId;
        $this->initFileUrl = $rawResult->FiasCompleteXmlUrl;
        $this->updateFileUrl = $rawResult->FiasDeltaXmlUrl;
    }

    public function getVersionId()
    {
        return $this->versionId;
    }

    public function getUpdateFileUrl()
    {
        return $this->updateFileUrl;
    }

    public function getInitFileUrl()
    {
        return $this->initFileUrl;
    }

    public function getUpdateFileName()
    {
        $fileName = basename($this->updateFileUrl);
        return $this->versionId . '_' . $fileName;
    }

    public function getInitFileName()
    {
        $fileName = basename($this->initFileUrl);
        return $this->versionId . '_' . $fileName;
    }
}
