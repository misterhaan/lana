import SettingsApi from "../api/settings.js";
import ValidateApi from "../api/validate.js";

const editTimeout = 250;
const activeTimers = {};

const Email = {
	data() {
		return {
			emails: [],
			addEmail: "",
			validation: {
				status: "",
				message: ""
			}
		};
	},
	computed: {
		primary() {
			return this.emails.find(email => email.isPrimary);
		},
		additional() {
			return this.emails.filter(email => !email.isPrimary);
		},
		canAddEmail() {
			return this.addEmail && this.validation.status == "valid";
		}
	},
	watch: {
		addEmail(newEmail) {
			if(activeTimers.email) {
				clearTimeout(activeTimers.email);
				delete activeTimers.email;
			}
			if(!newEmail)
				this.validation = { status: "", message: "" };
			else if(!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(newEmail))
				this.validation = { status: "invalid", message: "Please enter a valid email address" };
			else if(this.emails.some(e => e.address.toLowerCase() == newEmail.toLowerCase()))
				this.validation = { status: "invalid", message: "This email address has already been added" };
			else {
				this.validation = { status: "working", message: "Checking whether this email address has been claimed" };
				activeTimers.email = setTimeout(async () => {
					delete activeTimers.email;
					try {
						this.validation = await ValidateApi.Email(newEmail);
					} catch {
						this.validation = { status: "invalid", message: "Error encountered validating email address" };
					}
				}, editTimeout);
			}
		}
	},
	async created() {
		this.emails = await SettingsApi.ListEmail();
	},
	methods: {
		async SetPrimary(email) {
			await SettingsApi.SetPrimaryEmail(email.address);
			this.primary.isPrimary = false;
			email.isPrimary = true;
		},
		async RemoveEmail(email) {
			await SettingsApi.RemoveEmail(email.address);
			this.emails = this.emails.filter(e => e.address !== email.address);
		},
		async Add() {
			await SettingsApi.AddEmail(this.addEmail);
			this.emails.push({ address: this.addEmail, isPrimary: false });
			this.addEmail = "";
		},
		AddFromEnterKey() {
			if(this.canAddEmail)
				this.Add();
		}
	},
	template: /* html */ `
		<article id=email>
			<h2 class=inbox>Primary Email Address</h2>
			<p>
				When LAN Ahead contacts you by email, it will use your primary email
				address.
			</p>
			<ul v-if=primary><li>{{ primary.address }}</li></ul>

			<h2>Additional Email Addresses</h2>
			<p>
				Friends can find you by any email address listed on this page,
				including your primary email address.  Add any other emails your
				friends know to help them find you here.
			</p>
			<ul>
				<li v-for="email in additional" :key="email.address">
					{{ email.address }}
					<button class=inbox title="Make primary email address" @click=SetPrimary(email)></button>
					<button class=delete title="Remove email address" @click=RemoveEmail(email)></button>
				</li>
			</ul>
			<form @submit.prevent=Add class=call-to-action>
				<label class=single-line-fields>
					<input v-model.trim=addEmail keyup.enter=AddFromEnterKey required placeholder=me@example.com type=email title="Enter the email address you want to add"/>
					<span class=validation :class=validation.status :title=validation.message></span>
					<button class=add :disabled=!canAddEmail>Add</button>
				</label>
			</form>
		</article>
	`
};
export default Email;
