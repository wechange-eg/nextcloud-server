<?php
/**
 * @copyright 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

declare(strict_types=1);

namespace OC\Authentication\Login;

use OC\Core\Controller\LoginController;
use OCP\Authentication\Events\LoginFailedEvent;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\ILogger;
use OCP\IUserManager;
use OCP\Util;

class LoggedInCheckCommand extends ALoginCommand {

	/** @var ILogger */
	private $logger;
	/** @var IEventDispatcher */
	private $dispatcher;
	/** @var IUserManager */
	private $userManager;

	public function __construct(ILogger $logger, IEventDispatcher $dispatcher, IUserManager $userManager) {
		$this->logger = $logger;
		$this->dispatcher = $dispatcher;
		$this->userManager = $userManager;
	}

	public function process(LoginData $loginData): LoginResult {
		if ($loginData->getUser() === false) {
			$username = $loginData->getUsername();
			$ip = $loginData->getRequest()->getRemoteAddress();

			$this->logger->warning("Login failed: $username (Remote IP: $ip)");

			$uid = $username;
			Util::emitHook(
				'\OCA\Files_Sharing\API\Server2Server',
				'preLoginNameUsedAsUserName',
				['uid' => &$uid]
			);
			if($this->userManager->userExists($uid)) {
				$this->dispatcher->dispatchTyped(new LoginFailedEvent($uid));
			}

			return LoginResult::failure($loginData, LoginController::LOGIN_MSG_INVALIDPASSWORD);
		}

		return $this->processNextOrFinishSuccessfully($loginData);
	}

}
