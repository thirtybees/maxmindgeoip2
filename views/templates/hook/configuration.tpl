{**
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
 *}

{if $hasDatabase}
<div class="panel">
    <h3>{l s='You have valid database file' mod='maxmindgeoip2'}</h3>
    <div class="alert alert-success">
        <p>
            {l s='Congratulations! You have valid database file installed on your system.' mod='maxmindgeoip2'}
        </p>
    </div>
    <div>
        {if isset($dbType)}
            <dl>
                <dt>{l s='Database type' mod='maxmindgeoip2'}</dt>
                <dd>{$dbType}</dd>
            </dl>
        {/if}
        {if isset($dbName)}
            <dl>
                <dt>{l s='Database name' mod='maxmindgeoip2'}</dt>
                <dd>{$dbName}</dd>
            </dl>
        {/if}
        {if isset($dbTime)}
            <dl>
                <dt>{l s='Database build time' mod='maxmindgeoip2'}</dt>
                <dd>{$dbTime}</dd>
            </dl>
        {/if}
        {if isset($dbSize)}
            <dl>
                <dt>{l s='Records' mod='maxmindgeoip2'}</dt>
                <dd>{$dbSize}</dd>
            </dl>
        {/if}
        <dl>
            <dt>{l s='Database file' mod='maxmindgeoip2'}</dt>
            <dd><code>{$fullPath}</code></dd>
        </dl>
    </div>
</div>
{else}
    <div class="alert alert-danger">
        <p>
            {l s='MaxMind database not installed!' mod='maxmindgeoip2'}
        </p>
    </div>
{/if}

<div class="panel">
    <h3>{l s='Upload MaxMind Database' mod='maxmindgeoip2'}</h3>
    <div class="alert alert-info">
        <p>
            {l s='You need [1]MaxMind database file[/1] for this module to work properly. Follow these instructions to obtain newest version of the database:' mod='maxmindgeoip2' tags=["<strong>"]}
        </p>
        <ol>
            <li>
                {l s='[1]Create[/1] free MaxMind account, or [2]log in[/2] to your account if you already have one' mod='maxmindgeoip2' tags=["<a href='$createAccountUrl' target='_blank'>", "<a href='$loginAccountUrl' target='_blank'>"]}
            </li>
            <li>
                {l s='After registering, you will be able to download [1]GeoLite2 City[/1] database.' mod='maxmindgeoip2' tags=["<strong>"]}
            </li>
            <li>
                {l s='Extract downloaded archive and check that it contains database file [1]GeoLite2-City.mmdb[/1].' mod='maxmindgeoip2' tags=["<code>"]}
            </li>
            <li>
                {l s='Upload this database file [1]GeoLite2-City.mmdb[/1] using the form below' mod='maxmindgeoip2' tags=['<code>']}
            </li>
        </ol>
    </div>

    <h4>{l s='Upload database' mod='maxmindgeoip2'}</h4>
    <div class="row">
        <form action="{$action|escape:'html':'UTF-8'}" method="post" enctype="multipart/form-data" novalidate>
            <div class="col-xs-12">
                <div class="form-group">
                    <div class="col-sm-6">
                        <input id='db' type="file" name="db" accept=".mmdb" class="hide">
                        <div class="dummyfile input-group">
                            <span class="input-group-addon"><i class="icon-file"></i></span>
                            <input id='db-name' type="text" name="filename" readonly="">
                            <span class="input-group-btn">
                                <button id='db-select-button' type="button" name="submitAddDb" class="btn btn-default">
                                    <i class="icon-folder-open"></i>{l s='Select database file' mod='maxmindgeoip2'}
                                </button>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xs-12" style="padding-top:1em">
                <button id="submit" name="uploadDb" class="btn btn-primary" type="submit" disabled>
                    {l s='Upload database' mod='maxmindgeoip2'}
                </button>
            </div>
        </form>
    </div>

    <h4 style="padding-top: 3em">{l s='Troubleshooting' mod='maxmindgeoip2'}</h4>
    <div class="row">
        <div class="alert alert-info">
            <p>
                {l s='If you have problems uploading database files using form above, you can always do it manually' mod='maxmindgeoip2'}
            </p>
            <p>
                {l s='Use FTP program to upload database file to [1]%s[/1] inside your thirty bees installation.' mod='maxmindgeoip2' sprintf=[$localPath] tags=['<code>']}
            </p>
            <p>
                {l s='Full server path to this file is [1]%s[/1]' mod='maxmindgeoip2' sprintf=[$fullPath] tags=['<code>']}
            </p>
        </div>
    </div>
</div>

    <script type="text/javascript">
        $(document).ready(function () {
            $('#db-select-button').click(function (e) {
                $('#db').trigger('click');
            });

            $('#db-name').click(function (e) {
                $('#db').trigger('click');
            });

            $('#db-name').on('dragenter', function (e) {
                e.stopPropagation();
                e.preventDefault();
            });

            $('#db-name').on('dragover', function (e) {
                e.stopPropagation();
                e.preventDefault();
            });

            $('#db-name').on('drop', function (e) {
                e.preventDefault();
                var files = e.originalEvent.dataTransfer.files;
                $('#db')[0].files = files;
                $(this).val(files[0].name);
            });

            $('#db').change(function (e) {
                if ($(this)[0].files !== undefined) {
                    var files = $(this)[0].files;
                    var name = '';

                    $('#submit').attr('disabled', 'disabled');

                    $.each(files, function (index, value) {
                        $('#submit').attr('disabled', false);
                        name += value.name + ', ';
                    });

                    $('#db-name').val(name.slice(0, -2));
                } else {
                    var name = $(this).val().split(/[\\/]/);
                    $('#db-name').val(name[name.length - 1]);
                }
            });
        });
    </script>

