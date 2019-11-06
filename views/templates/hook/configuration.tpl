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

<div class="panel">
    <h3>{l s='MaxMind Database' mod='maxmindgeoip2'}</h3>

    {if $hasDatabase}
        <div class="alert alert-success">
            <p>
                {l s='You have valid database file' mod='maxmindgeoip2'}
            </p>
        </div>
    {else}
        <div class="alert alert-info">
            <p>
                {l s='Please download [1]MaxMind database file[/1] and save it to [2]%s[/2]' mod='maxmindgeoip2' sprintf=[$databaseFile] tags=["<a href=$databaseSource>", "<strong>"]}
            </p>
            <p>
                {l s='You can also click on the button bellow to download newest version of the database automatically' mod='maxmindgeoip2'}
            </p>
        </div>
    {/if}

    <div id="maxmind-error" class="alert alert-warning">
    </div>

    <button id="maxmind-download" class="btn btn-primary">
        <span class="downloading">
            {l s='Please wait, download in process' mod='maxmindgeoip2'}
            <i class="icon-spin icon-spinner"></i>
        </span>
        <span class="regular">
            {if $hasDatabase}
                {l s='Update database file' mod='maxmindgeoip2'}
            {else}
                {l s='Download database file' mod='maxmindgeoip2'}
            {/if}
        </span>
    </button>

</div>

<style>
    #maxmind-error,
    #maxmind-download:disabled .regular,
    #maxmind-download .downloading {
        display: none;
    }
    #maxmind-download .regular,
    #maxmind-download:disabled .downloading {
        display: inline;
    }
</style>

<script>
    $button = $('#maxmind-download');
    $error = $('#maxmind-error');
    $button.on('click', function() {
      $error.hide();
      $button.attr('disabled', 'disabled');
      $.ajax({
        type: "POST",
        url: window.location.href,
        async: true,
        dataType: 'json',
        data: {
          ajax: "1",
          action: "downloadDatabase",
        },
        success: function(data) {
          if (data && data.success) {
            window.location.reload(true);
          } else {
            $('.alert-success, .alert-info').hide();
            $button.attr('disabled', null);
            $error.text(data.error || 'Failed to download database').show();
          }
        },
      });
    });
</script>
