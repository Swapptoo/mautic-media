// Campaigns field.
Mautic.mediaCampaigns = function () {
    var $campaigns = mQuery('#media_campaign_settings:first:not(.campaigns-checked)');
    if ($campaigns.length) {
        $campaigns.addClass('campaigns-checked');

        if (!Mautic.getEntityId()) {
            mQuery('#media-campaigns-empty').removeClass('hide');
            mQuery('#media-campaigns-loading').addClass('hide');
            return;
        }
        else {
            mQuery('#media-campaigns-empty').addClass('hide');
            mQuery('#media-campaigns-loading').removeClass('hide');
        }

        // Retrieve the list of available campaigns via Ajax
        var campaigns = {},
            providerCampaigns = {},
            providerAccounts = {},
            $provider = mQuery('select[name="media[provider]"]:first'),
            campaignSettings = $campaigns.val(),
            campaignsJSONEditor,
            $campaignsJSONEditor;

        mQuery.ajax({
            showLoadingBar: true,
            url: mauticAjaxUrl,
            type: 'POST',
            data: {
                action: 'plugin:mauticMedia:getCampaignMap',
                mediaAccountId: Mautic.getEntityId(),
                provider: $provider.val(),
                campaignSettings: campaignSettings
            },
            dataType: 'json',
            cache: true,
            error: function (request, textStatus, errorThrown) {
                Mautic.processAjaxError(request, textStatus, errorThrown);
            },
            success: function (response) {
                if (typeof response.campaigns !== 'undefined') {
                    campaigns = response.campaigns;
                }
                if (typeof response.providerCampaigns !== 'undefined') {
                    providerCampaigns = response.providerCampaigns;
                }
                if (typeof response.providerAccounts !== 'undefined') {
                    providerAccounts = response.providerAccounts;
                }
                if (typeof response.campaignSettings !== 'undefined') {
                    var raw = JSON.stringify(response.campaignSettings, null, '  ');
                    $campaigns.val(raw);
                }

                // Grab the JSON Schema to begin rendering the form with
                // JSONEditor.
                mQuery.ajax({
                    showLoadingBar: true,
                    dataType: 'json',
                    cache: true,
                    url: mauticBasePath + '/' + mauticAssetPrefix + 'plugins/MauticMediaBundle/Assets/json/accountscampaigns.json',
                    success: function (data) {
                        Mautic.stopPageLoadingBar();
                        var schema = data;

                        if (campaigns.length) {
                            schema.definitions.campaignId.enumSource[0].source = campaigns;
                        }

                        if (providerCampaigns.length) {
                            schema.definitions.providerCampaignId.enumSource[0].source = providerCampaigns;
                        }

                        if (providerAccounts.length) {
                            schema.definitions.providerAccountId.enumSource[0].source = providerAccounts;
                        }

                        // Create our widget container for the JSON Editor.
                        $campaignsJSONEditor = mQuery('<div>', {
                            class: 'media_jsoneditor'
                        }).insertBefore($campaigns);

                        // Instantiate the JSON Editor based on our schema.
                        campaignsJSONEditor = new JSONEditor($campaignsJSONEditor[0], {
                            schema: schema,
                            disable_collapse: true,
                            disable_array_add: true,
                            disable_array_reorder: true,
                            disable_array_delete: true
                        });

                        $campaigns.change(function () {
                            // Load the initial value if applicable.
                            var raw = mQuery(this).val(),
                                obj;
                            if (raw.length) {
                                try {
                                    obj = mQuery.parseJSON(raw);
                                    if (typeof obj === 'object') {
                                        campaignsJSONEditor.setValue(obj);
                                    }
                                }
                                catch (e) {
                                    console.warn(e);
                                }
                            }
                        }).trigger('change');

                        // Persist the value to the JSON Editor.
                        campaignsJSONEditor.on('change', function (event) {
                            var obj = campaignsJSONEditor.getValue(),
                                provider = $provider.val(),
                                campaign,
                                providerAccount,
                                providerCampaign,
                                $pppp,
                                $multiple;
                            if (typeof obj === 'object') {
                                var raw = JSON.stringify(obj, null, '  ');
                                if (raw.length) {
                                    // Set the textarea.
                                    $campaigns.val(raw);
                                }
                            }
                            // Clickable Campaign headers.
                            $campaignsJSONEditor.find('div[data-schemapath$=".providerAccountId"] .control-label').each(function () {
                                providerAccount = mQuery(this).parent().find('select:first').val().replace('act_', '');
                                $pppp = $(this).parent().parent().parent().parent();
                                $multiple = $pppp.find('input[type="checkbox"][name$="[multiple]"]:first');
                                switch (provider) {
                                    case 'facebook':
                                        mQuery(this).html('<a href="https://www.facebook.com/adsmanager/manage/accounts?act=' + providerAccount + '" target="_blank">Facebook Account ' + providerAccount + '</a>');
                                        break;
                                    case 'google':
                                        mQuery(this).html('<a href="https://adwords.google.com/aw/overview?__e=' + providerAccount + '" target="_blank">Google Account ' + providerAccount + '</a>');
                                        break;
                                    case 'snapchat':
                                        mQuery(this).html('<a href="https://ads.snapchat.com/' + providerAccount + '/" target="_blank">Snapchat Account ' + providerAccount + '</a>');
                                        break;
                                    case 'bing':
                                        mQuery(this).html('<a href="https://ui.bingads.microsoft.com/campaign/Campaigns.m?aid=' + providerAccount + '/" target="_blank">Bing Account ' + providerAccount + '</a>');
                                        break;
                                }
                                var providerCampaignIds = 0;
                                $pppp.find('div[data-schemapath$=".providerCampaignId"] .control-label').each(function () {
                                    providerCampaignIds++;
                                    providerCampaign = mQuery(this).parent().find('select:first').val().replace('act_', '');
                                    switch (provider) {
                                        case 'facebook':
                                            mQuery(this).html('<a href="https://www.facebook.com/adsmanager/manage/adsets?act=' + providerAccount + '&selected_campaign_ids=' + providerCampaign + '" target="_blank">Facebook Campaign ' + providerCampaign + '</a>');
                                            break;
                                        case 'google':
                                            mQuery(this).html('<a href="https://adwords.google.com/aw/overview?__e=' + providerAccount + '&campaignId=' + providerCampaign + '" target="_blank">Google Campaign ' + providerCampaign + '</a>');
                                            break;
                                        case 'snapchat':
                                            mQuery(this).html('<a href="https://ads.snapchat.com/' + providerAccount + '/campaigns/' + providerCampaign + '" target="_blank">Snapchat Campaign ' + providerCampaign + '</a>');
                                            break;
                                        case 'bing':
                                            mQuery(this).html('<a href="https://ui.bingads.microsoft.com/campaign/Campaigns.m?aid=' + providerAccount + '&cid=' + providerCampaign + '" target="_blank">Bing Campaign ' + providerCampaign + '</a>');
                                            break;
                                    }
                                });
                                $pppp.find('div[data-schemapath$=".campaign"] .control-label, div[data-schemapath$=".campaignId"] .control-label').each(function () {
                                    campaign = mQuery(this).parent().find('select:first').val();
                                    if (campaign !== '0') {
                                        mQuery(this).html('<a href="' + mauticBasePath + '/s/campaigns/edit/' + campaign + '" target="_blank">Internal Campaign ' + campaign + '</a>');
                                    }
                                    else {
                                        mQuery(this).html('<span class="unmapped">Internal Campaign</span>');
                                    }
                                });
                                if (providerCampaignIds > 1) {
                                    if ($multiple.is(':checked')) {
                                        $pppp.addClass('multiple');
                                        $pppp.removeClass('single');
                                    }
                                    else {
                                        $pppp.addClass('single');
                                        $pppp.removeClass('multiple');
                                    }
                                }
                                else {
                                    $pppp.addClass('single');
                                    $pppp.removeClass('multiple');
                                    $multiple.attr('disabled', true).parent().parent().parent().addClass('hide');
                                }
                            });
                        });

                        if (providerAccounts.length) {
                            mQuery('#media-campaigns-empty').addClass('hide');
                            mQuery('#media-campaigns-loading').addClass('hide');
                        }
                        else {
                            mQuery('#media-campaigns-empty').removeClass('hide');
                            mQuery('#media-campaigns-loading').addClass('hide');
                        }

                        $campaignsJSONEditor.show();
                    },
                    error: function (request, textStatus, errorThrown) {
                        Mautic.processAjaxError(request, textStatus, errorThrown);
                    },
                });

            }
        });
    }
};
