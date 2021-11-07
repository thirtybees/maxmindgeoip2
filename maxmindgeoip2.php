<?php
/**
 * Copyright (C) 2019 thirty bees
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@thirtybees.com so we can send you a copy immediately.
 *
 * @author    thirty bees <modules@thirtybees.com>
 * @copyright 2019 thirty bees
 * @license   Academic Free License (AFL 3.0)
 */

if (!defined('_TB_VERSION_')) {
    exit;
}

require_once __DIR__.'/vendor/autoload.php';

use GeoIp2\Database\Reader;
use GeoIp2\Exception\AddressNotFoundException;

class MaxMindGeoIP2 extends Module
{

    const CREATE_ACCOUNT_URL = 'https://www.maxmind.com/en/geolite2/signup';
    const LOGIN_ACCOUNT_URL = 'https://www.maxmind.com/en/account/login';

    /**
     * MaxMindGeoIP2 constructor.
     *
     * @throws PrestaShopException
     */
    public function __construct()
    {
        $this->name = 'maxmindgeoip2';
        $this->tab = 'administration';
        $this->version = '1.1.0';
        $this->author = 'thirty bees';
        $this->controllers = [];
        $this->bootstrap = true;
        parent::__construct();
        $this->displayName = $this->l('MaxMind Geolocation');
        $this->description = $this->l('Geolocation services based on MaxMind GeoIP2 database');
        $this->need_instance = 0;
        $this->tb_versions_compliancy = '> 1.0.0';
        $this->tb_min_version = '1.0.0';
    }

    /**
     * Module installation process
     *
     * @return bool
     * @throws Adapter_Exception
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function install()
    {
        return (
            parent::install() &&
            $this->registerHook('actionGeoLocation')
        );
    }

    /**
     * Returns iso code for IP address
     *
     * @param array $params
     * @return string | null
     * @throws Adapter_Exception
     * @throws PrestaShopException
     */
    public function hookActionGeoLocation($params)
    {
        try {
            $reader = new Reader($this->getDatabaseFile());
            $city = $reader->city($params['ip']);
            return $city->country->isoCode;
        } catch (AddressNotFoundException $e) {
            return null;
        } catch (Exception $e) {
            Logger::addLog('MaxMindGeoIP2: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * @return string
     *
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function getContent()
    {
        if (Tools::isSubmit('uploadDb')) {
            if (isset($_FILES['db']) && isset($_FILES['db']['tmp_name'])) {
                $this->uploadDatabase($_FILES['db']['tmp_name'], $_FILES['db']['name']);
            }
        }

        $hasDatabase = $this->databaseExits(true);
        $this->context->smarty->assign([
            'hasDatabase' => $hasDatabase,
            'fullPath' => $this->getDatabaseFile(),
            'localPath' => str_replace(_PS_ROOT_DIR_ . '/', '', $this->getDatabaseFile()),
            'createAccountUrl' => static::CREATE_ACCOUNT_URL,
            'loginAccountUrl' => static::LOGIN_ACCOUNT_URL,
            'action' => $_SERVER['REQUEST_URI'],
        ]);
        if ($hasDatabase) {
            $metadata = $this->getMetadata();
            if ($metadata) {
                $this->context->smarty->assign([
                    'dbType' => $metadata->databaseType,
                    'dbTime' => date('Y-m-d H:i:s', $metadata->buildEpoch),
                    'dbName' => isset($metadata->description['en']) ? $metadata->description['en'] : null,
                    'dbSize' => $metadata->nodeCount
                ]);
            }
        }
        return $this->display(__FILE__, 'configuration.tpl');
    }

    /**
     * Returns path to database file
     *
     * @return string
     */
    protected function getDatabaseFile()
    {
        return rtrim(_PS_GEOIP_DIR_, '/') . '/db.mmdb';
    }

    /**
     * Returns true, if database file exists
     *
     * @param $checkValidity
     * @return bool
     */
    protected function databaseExits($checkValidity)
    {
        if (file_exists($this->getDatabaseFile())) {
            if ($checkValidity) {
                try {
                    new Reader($this->getDatabaseFile());
                } catch (Exception $e) {
                    return false;
                }
            }
            return true;
        }
        return false;
    }

    /**
     * @return \MaxMind\Db\Reader\Metadata | null
     */
    protected function getMetadata()
    {
        try {
            $reader = new Reader($this->getDatabaseFile());
            return $reader->metadata();
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Helper method to download database from
     * @param string $path
     * @param string $filename
     * @throws PrestaShopException
     */
    protected function uploadDatabase($path, $filename)
    {
        if (! preg_match('/\.mmdb$/', $filename)) {
            throw new PrestaShopException("Invalid file type");
        }

        $uncompressed = tempnam(_PS_CACHE_DIR_, 'maxmindgeoip2-raw');
        try {
            // copy uploaded file
            if (!Tools::copy($path, $uncompressed)) {
                throw new PrestaShopException(sprintf('Failed to copy uploaded file %s to %s', $path, $uncompressed));
            }

            // try to open database to check its validity
            new Reader($uncompressed);

            // copy extracted file to destination
            $target = $this->getDatabaseFile();
            if (file_exists($target)) {
                unlink($target);
            }
            if (!Tools::copy($uncompressed, $target)) {
                throw new PrestaShopException(sprintf("Failed to copy database to %s", $target));
            }
        } catch (\MaxMind\Db\Reader\InvalidDatabaseException $e) {
            throw new PrestaShopException("Uploaded file is not a valid MaxMind database");
        } finally {
            if (file_exists($uncompressed)) {
                unlink($uncompressed);
            }
        }
    }
}
