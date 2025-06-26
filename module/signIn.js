import { createApp } from "../external/vue.esm-browser.prod.js";
import TitleBar from "./component/titleBar.js";
import StatusBar from "./component/statusBar.js";
import AuthApi from "./api/auth.js";
import ValidateApi from "./api/validate.js";
import md5 from "../external/md5.js";

const pathPrefix = "/signin-";
const editTimeout = 250;
const activeTimers = {};

createApp({
	name: "LanaSignin",
	data() {
		return {
			siteId: false,
			regInfo: false,
			avatar: false,
			working: false,
			registering: false,
			missingSiteId: false,
			validation: {
				username: {
					status: "working",
					message: "Checking username validity"
				},
				email: {
					status: "working",
					message: "Checking email address validity"
				}
			}
		};
	},
	computed: {
		username() { // only here for the watch
			return this.regInfo && this.regInfo.username;
		},
		email() { // only here for the watch
			return this.regInfo && this.regInfo.email;
		},
		emailHash() {
			return this.regInfo && this.regInfo.email
				? md5(this.regInfo.email.toLowerCase())
				: "";
		},
		canRegister() {
			return this.regInfo.username && this.regInfo.username.length > 3 && !this.registering
				&& this.validation.email.status == "valid";
		}
	},
	watch: {
		username(newUsername) {
			if(activeTimers.username) {
				clearTimeout(activeTimers.username);
				delete activeTimers.username;
			}
			if(!newUsername)
				this.validation.username = { status: "invalid", message: "Username is required" };
			else if(newUsername.length < 4 || newUsername.length > 20)
				this.validation.username = { status: "invalid", message: "Username must be between 4 and 20 characters long" };
			else {
				this.validation.username = { status: "working", message: "Checking whether this username has been claimed" };
				activeTimers.username = setTimeout(() => {
					delete activeTimers.username;
					ValidateApi.Username(newUsername).done(result => {
						this.validation.username = result;
					}).fail(() => {
						this.validation.username = { status: "invalid", message: "Error encountered validating username" };
					});
				}, editTimeout);
			}
		},
		email(newEmail) {
			if(activeTimers.email) {
				clearTimeout(activeTimers.email);
				delete activeTimers.email;
			}
			if(!newEmail)
				this.validation.email = { status: "valid", message: "Email is not required" };
			else if(!/^[^@]+@[^.@]+(\.[^@.]+)+$/.test(newEmail))
				this.validation.email = { status: "invalid", message: "Does not look like an email address" };
			else if(newEmail.toLowerCase().endsWith("example.com"))
				this.validation.email = { status: "invalid", message: "Email address is not required, so feel free to leave it blank" };
			else {
				this.validation.email = { status: "working", message: "Checking for accounts with this email address" };
				activeTimers.email = setTimeout(() => {
					delete activeTimers.email;
					ValidateApi.Email(newEmail).done(result => {
						this.validation.email = result;
					}).fail(() => {
						this.validation.email = { status: "invalid", message: "Error encountered validating email address" };
					});
				}, editTimeout);
			}
		}
	},
	created() {
		if(location.pathname.startsWith(pathPrefix)) {
			this.siteId = location.pathname.substring(pathPrefix.length);
			this.working = true;
			AuthApi.SignIn(this.siteId, location.search.substring(1)).done(result => {
				if(result.registered)
					location.replace("." + result.returnHash);
				else {
					delete result.registered;
					this.avatar = result.avatar ? "account" : result.email ? "email" : "default";
					this.regInfo = result;
				}
			}).always(() => {
				this.working = false;
			});
		} else {
			throw new Error("Unable to determine login site from URL.  This may indicate a website configuration error.");
		}
	},
	methods: {
		Register() {
			this.registering = true;
			AuthApi.Register(this.siteId, this.regInfo.username, this.regInfo.realName, this.regInfo.email, this.avatar).done(() => {
				location.replace("." + this.regInfo.returnHash);
			}).always(() => {
				this.registering = false;
			});
		}
	},
	template: /*html*/ `
		<div id=lana>
			<titleBar></titleBar>
			<main>
				<h1 :class=siteId>Sign In</h1>
				<p v-if=working class=loading>Processing sign-in...</p>
				<template v-if=regInfo>
					<p>
						Welcome to LAN Ahead!  According to our records, this is your first
						sign-in with <a v-if=regInfo.profile :href=regInfo.profile>this {{regInfo.siteName}} account</a>{{regInfo.profile ? "" : "this " + regInfo.siteName + " account"}}.
						Information from the account you just signed in with are shown
						below — make changes if you like (real name and email are optional)
						and then you’re ready to get started with LAN Ahead!
					</p>
					<p>
						If this isn’t your first time here, you may have used a
						different account.  Sign in with that account instead, and then
						you can link this one from your settings.
					</p>
					<section class=single-line-fields id=new-user>
						<label title="Your username will identify you on LAN Ahead and is required">
							<span class=label>Username:</span>
							<input v-model.trim=regInfo.username required minlength=4 maxlength=20>
							<span class=validation :class=validation.username.status :title=validation.username.message></span>
						</label>
						<label title="Your real name will only be displayed to your friends you mark as able to see your real name">
							<span class=label>Real name:</span>
							<input v-model.trim=regInfo.realName maxlength=64>
						</label>
						<label title="Your primary email address will be used for emails sent from this site and to help friends find you.  You can add more email addresses in settings if friends might look for you under multiple addresses">
							<span class=label>Email:</span>
							<input type=email v-model.trim=regInfo.email maxlength=64>
							<span class=validation :class=validation.email.status :title=validation.email.message></span>
						</label>
					</section>
					<fieldset id=select-avatar>
						<legend>Avatar:</legend>
						<label v-if=regInfo.avatar title="Use the avatar associated with the account you just signed in">
							<button role=radio :aria-checked="avatar == 'account'" @click="avatar = 'account'">
								<img class=avatar :src=regInfo.avatar>
							</button>
							<span>{{regInfo.siteName}}</span>
						</label>
						<label v-if=regInfo.email title="Use the avatar assigned to your email at gravatar.com, or a random image">
							<button role=radio :aria-checked="avatar == 'email'" @click="avatar = 'email'">
								<img class=avatar :src="'https://www.gravatar.com/avatar/' + emailHash + '?s=128&amp;d=monsterid'">
							</button>
							<span>Gravatar</span>
						</label>
						<label title="Use the default avatar and blend in with other players">
							<button role=radio :aria-checked="avatar == 'default'" @click="avatar = 'default'">
								<img class=avatar src="external/user-secret.svg">
							</button>
							<span>Default</span>
						</label>
					</fieldset>
					<nav class=form id=new-user-buttons>
						<button :title="'Start LAN Ahead using your ' + regInfo.siteName + ' account'" @click=Register :disabled=!canRegister :class="{working: registering}">Continue</button>
					</nav>
				</template>
			</main>
			<statusBar></statusBar>
		</div>
	`
}).component("titleBar", TitleBar)
	.component("statusBar", StatusBar)
	.mount("#lana");
