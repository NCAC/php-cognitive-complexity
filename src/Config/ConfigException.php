<?php

declare(strict_types=1);

namespace NCAC\CognitiveComplexity\Config;

/**
 * Thrown when configuration cannot be loaded (e.g. an explicit --config file
 * that does not exist, or an unreadable / malformed cognitive.yaml).
 */
final class ConfigException extends \RuntimeException {

}
