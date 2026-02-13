import SettingsApi from "../api/settings.js";

const editTimeout = 250;
const activeTimers = {};

const Profile = {
	props: [
		"player"
	],
	data() {
		return {
			loading: false,
			username: "",
			realName: "",
			avatar: "external/user-secret.svg",
			profiles: [],
			validation: {
				username: {
					status: "",
					message: ""
				},
				email: {
					status: "",
					message: ""
				}
			}
		};
	},
	watch: {
		username(newUsername) {
			if(activeTimers.username) {
				clearTimeout(activeTimers.username);
				delete activeTimers.username;
			}
			if(!newUsername)
				this.validation.username = { status: "invalid", message: "Username is required" };
			else if(newUsername == this.player.username)
				this.validation.username = { status: "valid", message: "This is your current username" };
			else if(newUsername.length < 4 || newUsername.length > 20)
				this.validation.username = { status: "invalid", message: "Username must be between 4 and 20 characters long" };
			else {
				this.validation.username = { status: "working", message: "Checking whether this username has been claimed" };
				activeTimers.username = setTimeout(async () => {
					delete activeTimers.username;
					try {
						this.validation.username = await SettingsApi.SetUsername(newUsername);
						if(this.validation.username.status == "valid")
							this.player.username = newUsername;
					} catch {
						this.validation.username = { status: "invalid", message: "Error encountered validating username" };
					}
				}, editTimeout);
			}
		},
		async realName(newRealName) {
			if(activeTimers.realName) {
				clearTimeout(activeTimers.realName);
				delete activeTimers.realName;
			}
			if(newRealName != this.player.realName)
				activeTimers.realName = setTimeout(async () => {
					delete activeTimers.realName;
					await SettingsApi.SetRealName(newRealName);
					this.player.realName = newRealName;
				}, editTimeout);
		}
	},
	async created() {
		this.username = this.player.username;
		this.realName = this.player.realName;
		this.avatar = this.player.avatar;
		this.loading = true;
		try {
			const avatars = await SettingsApi.LoadAvatars();
			this.profiles = avatars;
		} finally {
			this.loading = false;
		}
	},
	methods: {
		SanitizeUsername() {
			this.username = this.username.replace(/[\/#?\s]/g, '');
		},
		async SetAvatar(profile) {
			await SettingsApi.SetAvatar(profile.id);
			this.player.avatar = this.avatar = profile.avatar;
		},
		SetDefaultAvatar() {
			this.SetAvatar({ id: 0, avatar: "external/user-secret.svg" });
		}
	},
	template: /* html */ `
		<article id=profile>
			<section class=single-line-fields>
				<label title="Your username will identify you on LAN Ahead and is required">
					<span class=label>Username:</span>
					<input v-model.trim=username required minlength=4 maxlength=20 pattern="[^\\/#?\\s]+" @input=SanitizeUsername>
					<span class=validation :class=validation.username.status :title=validation.username.message></span>
				</label>
				<label title="Your real name will only be displayed to your friends you mark as able to see your real name">
					<span class=label>Real name:</span>
					<input v-model.trim=realName maxlength=64>
				</label>
			</section>
			<fieldset id=select-avatar>
				<legend :class="{loading: loading}">Avatar:</legend>
				<label v-for="profile in profiles" :key="profile.id" :title="profile.type + ' avatar'">
					<button role=radio :aria-checked="avatar == profile.avatar" @click=SetAvatar(profile)>
						<img class=avatar :src="profile.avatar">
					</button>
					<span>{{ profile.type[0].toUpperCase() + profile.type.slice(1) }}</span>
				</label>
				<label title="Use the default avatar and blend in with other players">
					<button role=radio :aria-checked="avatar == 'external/user-secret.svg'" @click=SetDefaultAvatar>
						<img class=avatar src="external/user-secret.svg">
					</button>
					<span>Default</span>
				</label>
			</fieldset>
		</article>
	`
};
export default Profile;
