<?php
namespace MWHP\Inc\Traits;

trait Singleton {

    /**
     * Return the singleton instance using $GLOBALS to guarantee global scope.
     *
     * @return static
     */
    final public static function get_instance(): static {
        $called_class = static::class;
        $global_key = '__singleton_' . str_replace('\\', '_', $called_class);

        if (!isset($GLOBALS[$global_key])) {
            $GLOBALS[$global_key] = new static();

            if (method_exists($GLOBALS[$global_key], 'init')) {
                $GLOBALS[$global_key]->init();
            }

            do_action('mwhp_singleton_initialized', $called_class);
        }

        return $GLOBALS[$global_key];
    }

    /**
     * Prevent direct creation, cloning, and unserialization.
     */
    final protected function __construct() {}
    final protected function __clone() {}
    final public function __wakeup() {
        throw new \Exception("Cannot unserialize singleton.");
    }
}
