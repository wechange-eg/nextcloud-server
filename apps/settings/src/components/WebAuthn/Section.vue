<!--
  - @copyright 2020, Roeland Jago Douma <roeland@famdouma.nl>
  -
  - @author Roeland Jago Douma <roeland@famdouma.nl>
  -
  - @license GNU AGPL version 3 or any later version
  -
  - This program is free software: you can redistribute it and/or modify
  - it under the terms of the GNU Affero General Public License as
  - published by the Free Software Foundation, either version 3 of the
  - License, or (at your option) any later version.
  -
  - This program is distributed in the hope that it will be useful,
  - but WITHOUT ANY WARRANTY; without even the implied warranty of
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  - GNU Affero General Public License for more details.
  -
  - You should have received a copy of the GNU Affero General Public License
  - along with this program.  If not, see <http://www.gnu.org/licenses/>.
  -->

<template>
	<div id="security-webauthn" class="section">
		<h2>{{ t('settings', 'Passwordless Authentication') }}</h2>
		<p class="settings-hint hidden-when-empty">
			{{ t('settings', 'Set up your account for passwordless authentication following the FIDO2 standard.') }}
		</p>
		<p v-if="devices.length === 0">
			{{ t('twofactor_u2f', 'No devices configured.') }}
		</p>
		<p v-else>
			{{ t('twofactor_u2f', 'The following devices are configured for your account:') }}
		</p>
		<Device v-for="device in devices"
			:key="device.id"
			:name="device.name" />

		<p v-if="notSupported" class="warning">
			{{ t('settings', 'Your browser does not support Webauthn.') }}
		</p>

		<AddDevice v-if="!notSupported" />
	</div>
</template>

<script>
import AddDevice from './AddDevice'
import Device from './Device'
// import axios from '@nextcloud/axios'
// import confirmPassword from 'nextcloud-password-confirmation'

export default {
	components: {
		AddDevice,
		Device,
	},
	props: {
		devices: {
			type: Array,
			required: true,
		},
	},
	data() {
		return {
			notSupported: typeof (PublicKeyCredential) === 'undefined',
		}
	},
}
</script>

<style scoped>

</style>
