<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Digital Media Solutions, LLC
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticMediaBundle\Controller;

use Mautic\CampaignBundle\Entity\CampaignRepository;
use Mautic\CoreBundle\Controller\AjaxController as CommonAjaxController;
use Mautic\CoreBundle\Controller\AjaxLookupControllerTrait;
use Mautic\CoreBundle\Helper\InputHelper;
use MauticPlugin\MauticMediaBundle\Entity\StatRepository;
use MauticPlugin\MauticMediaBundle\Helper\CampaignSettingsHelper;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class AjaxController.
 */
class AjaxController extends CommonAjaxController
{
    use AjaxLookupControllerTrait;

    /**
     * Retrieve a list of campaigns for use in drop-downs for a specific Media Account.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *
     * @throws \Exception
     */
    protected function getCampaignMapAction(Request $request)
    {
        $mediaAccountId        = (int) InputHelper::clean($request->request->get('mediaAccountId'));
        $mediaProvider         = InputHelper::clean($request->request->get('mediaProvider'));
        $campaignSettingsField = html_entity_decode(InputHelper::clean($request->request->get('campaignSettings')));

        // Get all our Mautic internal campaigns.
        /** @var CampaignRepository */
        $campaignRepository = $this->get('mautic.campaign.model.campaign')->getRepository();
        $args               = [
            'orderBy'    => 'c.name',
            'orderByDir' => 'ASC',
        ];
        $campaigns          = $campaignRepository->getEntities($args);
        $campaignsField     = [
            [
                'value' => 0,
                'title' => count($campaigns) ? '-- No Campaign Mapped --' : '-- Please create a Campaign --',
            ],
        ];
        $campaignNames      = [];
        foreach ($campaigns as $campaign) {
            $id               = $campaign->getId();
            $published        = $campaign->isPublished();
            $name             = $campaign->getName();
            $category         = $campaign->getCategory();
            $category         = $category ? $category->getName() : '';
            $campaignsField[] = [
                'name'  => $name,
                'title' => htmlspecialchars_decode(
                    $name.($category ? '  ('.$category.')' : '').(!$published ? '  (unpublished)' : '')
                ),
                'value' => $id,
            ];
            // Adding periods to the end such that an unpublished campaign will be less likely to match against
            // a published campaign of the same name.
            $campaignNames[$id] = htmlspecialchars_decode($name).(!$published ? '.' : '');
        }

        // Get all recent and active campaigns and accounts from the provider.
        /** @var StatRepository $statRepository */
        $statRepository       = $this->get('mautic.media.model.media')->getStatRepository();
        $data                 = $statRepository->getProviderAccountsWithCampaigns($mediaAccountId, $mediaProvider);
        $providerAccountField = [
            [
                'value' => 0,
                'title' => count($data['accounts']) ? '-- Select an Account --' : '-- Please create an Account --',
            ],
        ];
        foreach ($data['accounts'] as $value => $title) {
            $providerAccountField[] = [
                'title' => htmlspecialchars_decode($title),
                'value' => $value,
            ];
        }
        $providerCampaignField = [
            [
                'value' => 0,
                'title' => count($data['campaigns']) ? '-- Select a Campaign --' : '-- Please create a Campaign --',
            ],
        ];
        foreach ($data['campaigns'] as $value => $title) {
            $providerCampaignField[] = [
                'title' => htmlspecialchars_decode($title),
                'value' => $value,
            ];
        }

        $campaignSettingsHelper = new CampaignSettingsHelper($campaignNames, $campaignSettingsField, $data);

        return $this->sendJsonResponse(
            [
                'campaigns'         => $campaignsField,
                'providerAccounts'  => $providerAccountField,
                'providerCampaigns' => $providerCampaignField,
                'campaignSettings'  => $campaignSettingsHelper->getAutoUpdatedCampaignSettings(),
            ]
        );
    }
}
