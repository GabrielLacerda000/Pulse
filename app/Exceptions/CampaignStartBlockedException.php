<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when a Campaign cannot be started: another Campaign owned by the
 * user is already running, or WhatsApp isn't connected. The message is
 * meant to be shown to the user as-is.
 */
final class CampaignStartBlockedException extends RuntimeException {}
