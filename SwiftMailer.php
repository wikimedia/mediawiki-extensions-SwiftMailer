<?php
/**
 * SwiftMailer Extension to handle email bounces in MediaWiki
 *
 * Copyright (c) 2014, Tony Thomas <01tonythomas@gmail.com>
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @file
 * @author Tony Thomas<01tonythomas@gmail.com>
 * @author Legoktm <legoktm@gmail.com>
 * @author Jeff Green <jgreen@wikimedia.org>
 * @license GPL-2.0
 * @ingroup Extensions
 */
$wgExtensionCredits['other'][] = array(
	'path' => __FILE__,
	'name' => 'SwiftMailer',
	'author' => array(
		'Tony Thomas'
	),
	'url' => "https://www.mediawiki.org/wiki/Extension:SwiftMailer",
	'descriptionmsg' => 'swiftmailer-desc',
	'version'  => '1.0',
	'license-name' => "GPL-2.0",
);

// Used when $wgSMTP['auth'] is set to true
// Set to 'ssl' or 'tls'. Eg: $wgSMTPAuthenticationMethod = 'tls'
$wgSMTPAuthenticationMethod = false;

//Hooks files
$wgAutoloadClasses[ 'SwiftMailerHooks' ] =  __DIR__ . '/SwiftMailerHooks.php';

//Register Hooks
$wgHooks[ 'AlternateUserMailer' ][] = 'SwiftMailerHooks::onAlternateUserMailer';

/*Messages Files */
$wgMessagesDirs[ 'SwiftMailer' ] = __DIR__ . '/i18n';

if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
	require_once __DIR__ . '/vendor/autoload.php';
}
