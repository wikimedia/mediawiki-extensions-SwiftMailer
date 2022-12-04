<?php
declare( strict_types=1 );

/**
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 *
 * @file
 */

namespace MediaWiki\Extension\SwiftMailer;

use ConfigException;
use MailAddress;
use MediaWiki\Hook\AlternateUserMailerHook;
use MediaWiki\MediaWikiServices;
use Symfony\Component\Mailer\Exception\InvalidArgumentException;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

/**
 * Hooks for SwiftMailer extension
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
 * @author Tony Thomas <01tonythomas@gmail.com>
 * @author Legoktm <legoktm@gmail.com>
 * @author Jeff Green <jgreen@wikimedia.org>
 * @license GPL-2.0-or-later
 * @ingroup Extensions
 */
class MailHooks implements AlternateUserMailerHook {

	/**
	 * @inheritDoc
	 */
	public function onAlternateUserMailer( $headers, $to, $from, $subject, $body ) {
		if ( !is_array( $to ) ) {
			$to = [ $to ];
		}
		$recipients = array_map( static function ( MailAddress $recipient ) {
			return new Address( $recipient->address, $recipient->name ?? '' );
		}, $to );

		$message = ( new Email() )
			->subject( $subject )
			->from( new Address( $from->address, $from->name ) )
			->to( ...$recipients )
			->text( $body );

		$returnPath = $headers['Return-Path'] ?? null;
		if ( $returnPath !== null ) {
			$message->returnPath( $returnPath );
		}

		try {
			$transport = self::getTransport();
		} catch ( ConfigException | InvalidArgumentException | TransportException $e ) {
			wfLogWarning( "SwiftMailer: Transport configuration for SwiftMailer failed: $e" );
			return false;
		}

		if ( $transport === null ) {
			wfLogWarning( 'SwiftMailer: Tried to use un-configured transport. Is $wgSMTP set correctly?' );
			return false;
		}

		wfDebug( "Sending mail via Symfony::Mail\n" );

		try {
			$transport->send( $message );
		} catch ( TransportExceptionInterface $e ) {
			wfDebugLog( 'SwiftMailer', "Symfony Mailer failed: $e" );
		}

		// Alternate Mailer hooks should return false to skip regular false sending
		return false;
	}

	/**
	 * Generate the SwiftMailer transport object
	 *
	 * @return Transport\TransportInterface|null
	 * @throws ConfigException|InvalidArgumentException
	 */
	protected static function getTransport(): ?Transport\TransportInterface {
		static $transport = null;

		if ( !$transport ) {
			$smtp = MediaWikiServices::getInstance()->getMainConfig()->get( 'SMTP' );
			$config = MediaWikiServices::getInstance()->getConfigFactory()->makeConfig( 'SwiftMailer' );
			$tls = $config->get( 'SMTPAuthenticationMethod' );
			$tlsNoVerify = $config->get( 'SMTPTlsPeerVerification' );

			if ( is_array( $smtp ) ) {
				$transport = Transport::fromDsn( sprintf(
					'smtp%s://%s:%s@%s:%d%s',
					( $tls === 'tls' && $smtp['auth'] === true ) ? 's' : '',
					$smtp['username'],
					$smtp['password'],
					$smtp['host'],
					$smtp['port'],
					$tlsNoVerify === true ? '?verify_peer=0' : '',
				) );
			} else {
				throw new TransportException();
			}
		}

		return $transport;
	}
}
