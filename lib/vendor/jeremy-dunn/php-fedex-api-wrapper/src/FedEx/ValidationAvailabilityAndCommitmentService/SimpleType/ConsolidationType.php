<?php
namespace FedEx\ValidationAvailabilityAndCommitmentService\SimpleType;

use FedEx\AbstractSimpleType;

/**
 * ConsolidationType
 *
 * @author      Jeremy Dunn <jeremy@jsdunn.info>
 * @package     PHP FedEx API wrapper
 * @subpackage  Validation Availability And Commitment Service Service
 */
class ConsolidationType extends AbstractSimpleType
{
    const _INTERNATIONAL_DISTRIBUTION_FREIGHT = 'INTERNATIONAL_DISTRIBUTION_FREIGHT';
    const _INTERNATIONAL_ECONOMY_DISTRIBUTION = 'INTERNATIONAL_ECONOMY_DISTRIBUTION';
    const _INTERNATIONAL_GROUND_DIRECT_DISTRIBUTION = 'INTERNATIONAL_GROUND_DIRECT_DISTRIBUTION';
    const _INTERNATIONAL_GROUND_DISTRIBUTION = 'INTERNATIONAL_GROUND_DISTRIBUTION';
    const _INTERNATIONAL_PRIORITY_DISTRIBUTION = 'INTERNATIONAL_PRIORITY_DISTRIBUTION';
    const _TRANSBORDER_DISTRIBUTION = 'TRANSBORDER_DISTRIBUTION';
}
