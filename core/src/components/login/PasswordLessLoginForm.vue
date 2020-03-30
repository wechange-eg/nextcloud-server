<template>
	<form v-if="isHttps && hasPublicKeyCredential"
		ref="loginForm"
		method="post"
		name="login"
		@submit.prevent="submit">
		<fieldset>
			<p class="grouptop groupbottom">
				<input id="user"
					ref="user"
					v-model="user"
					type="text"
					name="user"
					:autocomplete="autoCompleteAllowed ? 'on' : 'off'"
					:placeholder="t('core', 'Username or email')"
					:aria-label="t('core', 'Username or email')"
					required
					@change="$emit('update:username', user)">
				<label for="user" class="infield">{{ t('core', 'Username or	email') }}</label>
			</p>

			<LoginButton :loading="loading" :inverted-colors="invertedColors" @click="authenticate" />
		</fieldset>
	</form>
	<div v-else-if="!hasPublicKeyCredential">
		{{ t('core', 'Passwordless authentication is not supported in your browser.')}}
	</div>
	<div v-else-if="!isHttps">
		{{ t('core', 'Passwordless authentication is only available over a secure connection.')}}
	</div>
</template>

<script>
import {
	startAuthentication,
	finishAuthentication,
} from '../../service/WebAuthnAuthenticationService'
import LoginButton from './LoginButton'

export default {
	name: 'PasswordLessLoginForm',
	components: {
		LoginButton,
	},
	props: {
		username: {
			type: String,
			default: '',
		},
		redirectUrl: {
			type: String,
		},
		invertedColors: {
			type: Boolean,
			default: false,
		},
		autoCompleteAllowed: {
			type: Boolean,
			default: true,
		},
		isHttps: {
			type: Boolean,
			default: false,
		},
		hasPublicKeyCredential: {
			type: Boolean,
			default: false,
		}
	},
	data() {
		return {
			user: this.username,
			loading: false,
		}
	},
	methods: {
		authenticate() {
			console.debug('passwordless login initiated')

			this.getAuthenticationData(this.user)
				.then(publicKey => {
					console.debug(publicKey)
					return publicKey
				})
				.then(this.sign)
				.then(this.completeAuthentication)
		},
		getAuthenticationData(uid) {
			const base64urlDecode = function(input) {
				// Replace non-url compatible chars with base64 standard chars
				input = input
					.replace(/-/g, '+')
					.replace(/_/g, '/')

				// Pad out with standard base64 required padding characters
				const pad = input.length % 4
				if (pad) {
					if (pad === 1) {
						throw new Error('InvalidLengthError: Input base64url string is the wrong length to determine padding')
					}
					input += new Array(5 - pad).join('=')
				}

				return window.atob(input)
			}

			return startAuthentication(uid)
				.then(publicKey => {
					console.debug('Obtained PublicKeyCredentialRequestOptions')

					publicKey.challenge = Uint8Array.from(base64urlDecode(publicKey.challenge), c => c.charCodeAt(0))
					publicKey.allowCredentials = publicKey.allowCredentials.map(function(data) {
						return {
							...data,
							'id': Uint8Array.from(base64urlDecode(data.id), c => c.charCodeAt(0)),
						}
					})

					console.debug('Converted PublicKeyCredentialRequestOptions')
					console.debug(publicKey)
					return publicKey
				})
				.catch(error => {
					console.debug('GOT AN ERROR WHILE OBTAINING DATA!')
					console.debug(error) // Example: timeout, interaction refused...
				})
		},
		sign(publicKey) {
			const arrayToBase64String = function(a) {
				return window.btoa(String.fromCharCode(...a))
			}

			return navigator.credentials.get({ publicKey })
				.then(data => {
					console.debug(data)
					console.debug(new Uint8Array(data.rawId))
					console.debug(arrayToBase64String(new Uint8Array(data.rawId)))
					return {
						id: data.id,
						type: data.type,
						rawId: arrayToBase64String(new Uint8Array(data.rawId)),
						response: {
							authenticatorData: arrayToBase64String(new Uint8Array(data.response.authenticatorData)),
							clientDataJSON: arrayToBase64String(new Uint8Array(data.response.clientDataJSON)),
							signature: arrayToBase64String(new Uint8Array(data.response.signature)),
							userHandle: data.response.userHandle ? arrayToBase64String(new Uint8Array(data.response.userHandle)) : null,
						},
					}
				})
				.then(challenge => {
					console.debug(challenge)
					return challenge
				})
				.catch(error => {
					console.debug('GOT AN ERROR!')
					console.debug(error) // Example: timeout, interaction refused...
				})
		},
		completeAuthentication(challenge) {
			console.debug('TIME TO COMPLETE')

			const location = this.redirectUrl

			return finishAuthentication(JSON.stringify(challenge))
				.then(data => {
					console.debug('Logged in redirecting')
					window.location.href = location
				})
				.catch(error => {
					console.debug('GOT AN ERROR WHILE SUBMITTING CHALLENGE!')
					console.debug(error) // Example: timeout, interaction refused...
				})
		},
		submit() {
			// noop
		},
	},
}
</script>

<style scoped>

</style>
