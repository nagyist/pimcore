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

namespace Pimcore\Model\Property\Predefined;

use Pimcore\Model;
use Symfony\Component\Uid\Uuid as Uid;

/**
 * @internal
 *
 * @property \Pimcore\Model\Property\Predefined $model
 */
class Dao extends Model\Dao\PimcoreLocationAwareConfigDao
{
    /**
     * @deprecated Will be removed in Pimcore 11
     */
    private const STORAGE_DIR = 'PIMCORE_CONFIG_STORAGE_DIR_PREDEFINED_PROPERTIES';

    /**
     * @deprecated Will be removed in Pimcore 11
     */
    private const WRITE_TARGET = 'PIMCORE_WRITE_TARGET_PREDEFINED_PROPERTIES';

    private const CONFIG_KEY = 'predefined_properties';

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
            'containerConfig' => $config['properties']['predefined']['definitions'],
            'settingsStoreScope' => 'pimcore_predefined_properties',
            'storageDirectory' => $storageDirectory,
            'legacyConfigFile' => 'predefined-properties.php',
            'writeTargetEnvVariableName' => self::WRITE_TARGET,
            'writeTarget' => $writeTarget
        ]);
    }

    /**
     * @param string|null $id
     *
     * @throws Model\Exception\NotFoundException
     */
    public function getById($id = null)
    {
        if ($id != null) {
            $this->model->setId($id);
        }

        $data = $this->getDataByName($this->model->getId());

        if ($data && $id != null) {
            $data['id'] = $id;
        }

        if ($data) {
            $this->assignVariablesToModel($data);
        } else {
            throw new Model\Exception\NotFoundException(sprintf(
                'Predefined property with ID "%s" does not exist.',
                $this->model->getId()
            ));
        }
    }

    /**
     * @param string|null $key
     *
     * @throws Model\Exception\NotFoundException
     */
    public function getByKey($key = null)
    {
        if ($key != null) {
            $this->model->setKey($key);
        }
        $key = $this->model->getKey();

        $list = new Listing();
        /** @var Model\Property\Predefined[] $properties */
        $properties = array_values(array_filter($list->getProperties(), function ($item) use ($key) {
            return $item->getKey() == $key;
        }
        ));

        if (count($properties) && $properties[0]->getId()) {
            $this->assignVariablesToModel($properties[0]->getObjectVars());
        } else {
            throw new Model\Exception\NotFoundException(sprintf(
                'Predefined property with key "%s" does not exist.',
                $this->model->getKey()
            ));
        }
    }

    /**
     * @throws \Exception
     */
    public function save()
    {
        if (!$this->model->getId()) {
            $this->model->setId(Uid::v4());
        }
        $ts = time();
        if (!$this->model->getCreationDate()) {
            $this->model->setCreationDate($ts);
        }
        $this->model->setModificationDate($ts);

        $dataRaw = $this->model->getObjectVars();
        $data = [];
        $allowedProperties = ['name', 'description', 'key', 'type', 'data',
            'config', 'ctype', 'inheritable', 'creationDate', 'modificationDate', ];

        foreach ($dataRaw as $key => $value) {
            if (in_array($key, $allowedProperties)) {
                $data[$key] = $value;
            }
        }
        $this->saveData($this->model->getId(), $data);
    }

    /**
     * Deletes object from database
     */
    public function delete()
    {
        $this->deleteData($this->model->getId());
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareDataStructureForYaml(string $id, $data)
    {
        return [
            'pimcore' => [
                'properties' => [
                    'predefined' => [
                        'definitions' => [
                            $id => $data,
                        ],
                    ],
                ],
            ],
        ];
    }
}
