<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         3.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\I18n;

use Aura\Intl\TranslatorLocator;
use Cake\Cache\Cache;

/**
 * Constructs and stores instances of translators that can be
 * retrieved by name and locale.
 */
class TranslatorRegistry extends TranslatorLocator {

/**
 * A list of loader functions indexed by domain name. Loaders are
 * callables that are invoked as a default for building translation
 * packages where none can be found for the combination of translator
 * name and locale.
 *
 * @var array
 */
	protected $_loader;

/**
 * Gets a translator from the registry by package for a locale.
 *
 * @param string $name The translator package to retrieve.
 * @param string $locale The locale to use; if empty, uses the default
 * locale.
 * @return \Aura\Intl\TranslatorInterface A translator object.
 * @throws \Aura\Intl\Exception If no translattor with that name could be found
 * for the given locale.
 */
	public function get($name, $locale = null) {
		if ($locale === null) {
			$locale = $this->getLocale();
		}

		if (!isset($this->registry[$name][$locale])) {
			$key = "translations.$name.$locale";
			return Cache::remember($key, function() use ($name, $locale) {
				try {
					return parent::get($name, $locale);
				} catch (\Aura\Intl\Exception $e) {
					if (!isset($this->_loaders[$name])) {
						throw $e;
					}
					return $this->_getFromLoader($name, $locale);
				}
			}, '_cake_core_');
		}

		return $this->registry[$name][$locale];
	}

/**
 * Registers a loader function for a package name that will be used as a fallback
 * in case no package with that name can be found.
 *
 * Loader callbacks will get as first argument the package name and the locale as
 * the second argument.
 *
 * @param string $name The name of the translator package to register a loader for
 * @param callable $loader A callable object that should return a Package
 * @return \Aura\Intl\TranslatorInterface A translator object.
 */
	public function registerLoader($name, callable $loader) {
		$this->_loaders[$name] = $loader;
	}

/**
 * Registers a new package by passing the register loaded function for the
 * package name.
 *
 * @param string $name The name of the translator package
 * @param string $locale The locale that should be built the package for
 * @return \Aura\Intl\TranslatorInterface A translator object.
 */
	protected function _getFromLoader($name, $locale) {
		$this->packages->set($name, $locale, function() use ($name, $locale) {
			return $this->_loaders[$name]($name, $locale);
		});
		return parent::get($name, $locale);
	}

}