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

    const DB_SOURCE = 'http://geolite.maxmind.com/download/geoip/database/GeoLite2-City.mmdb.gz';

    /**
     * MaxMindGeoIP2 constructor.
     *
     * @throws PrestaShopException
     */
    public function __construct()
    {
        $this->name = 'maxmindgeoip2';
        $this->tab = 'administration';
        $this->version = '1.0.0';
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
        $this->context->smarty->assign([
            'hasDatabase' => $this->databaseExits(true),
            'databaseFile' => $this->getDatabaseFile(),
            'databaseSource' => static::DB_SOURCE,
        ]);
        return $this->display(__FILE__, 'configuration.tpl');
    }

    /**
     * Ajax handler for database download
     */
    public function ajaxProcessDownloadDatabase()
    {
        try {
            $this->downloadDatabase();
            die(json_encode(['success' => true]));
        } catch (Exception $e) {
            die(json_encode(['success' => false, 'error' => $e->getMessage()]));
        }
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
     * Helper method to download database from
     * @throws Exception
     */
    protected function downloadDatabase()
    {
        $compressed = tempnam(_PS_CACHE_DIR_, 'maxmindgeoip2-gz');
        $uncompressed = tempnam(_PS_CACHE_DIR_, 'maxmindgeoip2-raw');
        try {
            // download gzip file
            if (! Tools::copy(static::DB_SOURCE, $compressed)) {
               throw new PrestaShopException(sprintf('Failed to download database from %s', static::DB_SOURCE));
            }

            // extract downloaded gz file
            $bufferSize = 4096; // read 4kb at a time
            $input = gzopen($compressed, 'rb');
            if (! $input) {
                throw new PrestaShopException('Failed to read downloaded file');
            }
            $out = fopen($uncompressed, 'wb');
            if (! $out) {
                throw new PrestaShopException('Failed to create output file');
            }
            while (!gzeof($input)) {
                fwrite($out, gzread($input, $bufferSize));
            }
            fclose($out);
            gzclose($input);

            // try to open database to check its validity
            new Reader($uncompressed);

            // copy extracted file to destination
            $target = $this->getDatabaseFile();
            if (file_exists($target)) {
                unlink($target);
            }
            if (! Tools::copy($uncompressed, $target)) {
                throw new PrestaShopException(sprintf("Failed to copy database to %s", $target));
            }
        } finally {
            if (file_exists($compressed)) {
                unlink($compressed);
            }
            if (file_exists($uncompressed)) {
                unlink($uncompressed);
            }
        }
    }
}
