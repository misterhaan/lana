import AppName from "../appName.js";
import SettingsApi from "../api/settings.js";
import AuthApi from "../api/auth.js";

const Accounts = {
	props: [
		"auths"
	],
	data() {
		return {
			loading: false,
			accounts: false
		};
	},
	created() {
		this.loading = true;
		SettingsApi.ListAccounts().done(result => {
			this.accounts = result;
		}).always(() => {
			this.loading = false;
		});
	},
	methods: {
		Add(site) {
			AuthApi.GetSignInUrl(site, location.hash).done(result => {
				location = result;
			});
		},
		Unlink(account) {
			SettingsApi.UnlinkAccount(account.site, account.id).done(() => {
				this.accounts.splice(this.accounts.indexOf(account), 1);
			});
		}
	},
	template: /*html*/ `
		<article id=linkedAccounts>
			<section>
				<h2 :class="{working: loading}">Linked Accounts</h2>
				<p v-if=accounts>You currently have {{accounts.length}} account{{accounts.length == 1 ? "" : "s"}} linked to ${AppName.Full}:</p>
				<ul v-if=accounts class="cards filledIcons">
					<li v-for="account in accounts" class=linkedAccount :class=account.site>
						<a :href=account.url title="View the profile for this account"><img class=avatar :src=account.avatar></a>
						<div class=actions>
							<button v-if="accounts.length > 1" class=unlink title="Unlink this account so it can no longer be used to sign in here" @click=Unlink(account)></button>
						</div>
					</li>
				</ul>
			</section>

			<section>
				<h2>Authorize Another Account</h2>
				<p>
					Choose an account provider below to sign in and add it for ${AppName.Full}
					sign in.  You can add multiple accounts from the same provider.
				</p>
				<nav id=authNew class=filledIcons>
					<button role=link v-for="auth in auths" :class=auth.id :title="'Link your ' + auth.name + ' account'" @click=Add(auth.id)></button>
				</nav>
			</section>
		</article>
	`
};
export default Accounts;
