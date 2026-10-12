<?php

/**
 * @file
 * Convenience file that registers autoload handler for HTML Purifier.
 */

if (function_exists('spl_autoload_register') && function_exists('spl_autoload_unregister')) {
    // We need unregister for our pre-registering functionality
    HTMLPurifier_Bootstrap::registerAutoload();
    if (function_exists('__autoload')) {
        // Be polite and ensure that userland autoload gets retained
        spl_autoload_register('__autoload');
    }
}

// vim: et sw=4 sts=4
