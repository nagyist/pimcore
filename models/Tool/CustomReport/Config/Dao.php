<?php

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Model\Tool\CustomReport\Config;

use Pimcore\Model;

/**
 * @internal
 *
 * @property \Pimcore\Model\Tool\CustomReport\Config $model
 */
class Dao extends Model\Dao\PimcoreLocationAwareConfigDao
{
    /**
     * @deprecated Will be removed in Pimcore 11
     */
    private const STORAGE_DIR = 'PIMCORE_CONFIG_STORAGE_DIR_CUSTOM_REPORTS';

    /**
     * @deprecated Will be removed in Pimcore 11
     */
    private const WRITE_TARGET = 'PIMCORE_WRITE_TARGET_CUSTOM_REPORTS';

    private const CONFIG_KEY = 'custom_reports';

    public function configure()
    {
        $config = \Pimcore::getContainer()->getParameter('pimcore.config');

        $storageDirectory = null;
        if(array_key_exists('directory', $config['storage'][self::CONFIG_KEY])) {
            $storageDirectory = $config['storage'][self::CONFIG_KEY]['directory'];
        } elseif (array_key_exists(self::STORAGE_DIR, $_SERVER)) {
            $storageDirectory = $_SERVER[self::STORAGE_DIR];
            trigger_deprecation('pimcore/pimcore', '10.6',
                sprintf('Setting storage directory (%s) in the .env file is deprecated, instead use the symfony config. It will be removed in Pimcore 11.',  self::STORAGE_DIR));
        } else {
            $storageDirectory = PIMCORE_CONFIGURATION_DIRECTORY . '/' . self::CONFIG_KEY;
        }

        $writeTarget = null;
        if(array_key_exists('target', $config['storage'][self::CONFIG_KEY])) {
            $writeTarget = $config['storage'][self::CONFIG_KEY]['target'];
        } elseif (array_key_exists(self::WRITE_TARGET, $_SERVER)) {
            $writeTarget = $_SERVER[self::WRITE_TARGET];
            trigger_deprecation('pimcore/pimcore', '10.6',
                sprintf('Setting write targets (%s) in the .env file is deprecated, instead use the symfony config. It will be removed in Pimcore 11.',  self::WRITE_TARGET));
        }

        parent::configure([
            'containerConfig' => $config['custom_report']['definitions'],
            'settingsStoreScope' => 'pimcore_custom_reports',
            'storageDirectory' => $storageDirectory,
            'legacyConfigFile' => 'custom-reports.php',
            'writeTargetEnvVariableName' => self::WRITE_TARGET,
            'writeTarget' => $writeTarget
        ]);
    }

    /**
     * @param string|null $id
     *
     * @throws Model\Exception\NotFoundException
     */
    public function getByName($id = null)
    {
        if ($id != null) {
            $this->model->setName($id);
        }

        $data = $this->getDataByName($this->model->getName());

        if ($data && $id != null) {
            $data['id'] = $id;
        }

        if ($data) {
            $this->assignVariablesToModel($data);
            $this->model->setName($data['id']);
        } else {
            throw new Model\Exception\NotFoundException(sprintf(
                'Custom report config with name "%s" does not exist.',
                $this->model->getName()
            ));
        }
    }

    /**
     * @throws \Exception
     */
    public function save()
    {
        $ts = time();
        if (!$this->model->getCreationDate()) {
            $this->model->setCreationDate($ts);
        }
        $this->model->setModificationDate($ts);

        $dataRaw = $this->model->getObjectVars();
        $data = [];
        $allowedProperties = ['name', 'sql', 'dataSourceConfig', 'columnConfiguration', 'niceName', 'group', 'xAxis',
            'groupIconClass', 'iconClass', 'reportClass', 'creationDate', 'modificationDate', 'menuShortcut', 'chartType', 'pieColumn',
            'pieLabelColumn', 'yAxis', 'shareGlobally', 'sharedUserNames', 'sharedRoleNames', ];

        foreach ($dataRaw as $key => $value) {
            if (in_array($key, $allowedProperties)) {
                $data[$key] = $value;
            }
        }
        $this->saveData($this->model->getName(), $data);
    }

    /**
     * Deletes object from database
     */
    public function delete()
    {
        $this->deleteData($this->model->getName());
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareDataStructureForYaml(string $id, $data)
    {
        return [
            'pimcore' => [
                'custom_report' => [
                    'definitions' => [
                        $id => $data,
                    ],
                ],
            ],
        ];
    }
}
