import SettingsApi from "../api/settings.js";
import ValidateApi from "../api/validate.js";
import Visibility from "../visibility.js";
import DragDrop from "../mixin/dragDrop.js";

const editTimeout = 250;
const activeTimers = {};

const LinkList = {
	props: [
		"links"
	],
	emits: ["remove"],
	methods: {
		Remove(link) {
			this.$emit("remove", link);
		}
	},
	mixins: [
		DragDrop
	],
	template: /* html */ `
		<ol>
			<li v-for="link in links" v-draggable="{name: link.name, data: link}">
				<a :href=link.url :class=link.type>{{link.name}}</a>
				<button v-if=link.canDelete class=delete tooltip="remove this link" @click.stop=Remove(link)></button>
			</li>
		</ol>
	`
};

const Links = {
	data() {
		return {
			loading: false,
			links: null,
			addUrl: "",
			validation: {
				status: "",
				message: ""
			}
		};
	},
	computed: {
		none() {
			return this.links?.filter(l => l.visibility == Visibility.Self);
		},
		friend() {
			return this.links?.filter(l => l.visibility == Visibility.Friends);
		},
		user() {
			return this.links?.filter(l => l.visibility == Visibility.Players);
		},
		everyone() {
			return this.links?.filter(l => l.visibility == Visibility.Everyone);
		},
		hasEmails() {
			return this.emails?.length;
		},
		hasAccounts() {
			return this.accounts?.length;
		},
		canAddLink() {
			return this.addUrl.length > 0 && this.validation.status == "valid";
		}
	},
	watch: {
		addUrl(newUrl) {
			if(activeTimers.url) {
				clearTimeout(activeTimers.url);
				delete activeTimers.url;
			}
			if(!newUrl)
				this.validation = { status: "", message: "" };
			else
				try {
					const url = new URL(newUrl);
					if(!["http:", "https:"].includes(url.protocol))
						this.validation = { status: "invalid", message: "Only HTTP and HTTPS URLs are supported" };
					else {
						this.validation = { status: "working", message: "Validating URL..." };
						activeTimers.url = setTimeout(async () => {
							delete activeTimers.url;
							this.validation = await ValidateApi.AddLink(newUrl);
						}, editTimeout);
					}
				} catch {
					this.validation = { status: "invalid", message: "Unable to parse URL" };
				}
		}
	},
	async created() {
		this.Visibility = Visibility;
		this.loading = true;
		try {
			this.links = await SettingsApi.ListLinks();
		} finally {
			this.loading = false;
		}
	},
	methods: {
		SetVisibility(link, visibility) {
			link.visibility = visibility;
			SettingsApi.LinkVisibility(link.id, visibility);
		},
		AddFromEnterKey() {
			if(this.canAddLink)
				this.Add();
		},
		async Add() {
			if(!this.canAddLink)
				throw new Error("Cannot add invalid link");
			this.links.push(await SettingsApi.AddLink(this.addUrl));
			this.addUrl = "";
			this.validation = { status: "", message: "" };
		},
		async Remove(link) {
			await SettingsApi.RemoveLink(link.id);
			this.links.splice(this.links.indexOf(link), 1);
		}
	},
	mixins: [
		DragDrop
	],
	components: {
		LinkList: LinkList
	},
	template: /* html */ `
		<article id=links>
			<section class="visibility none" v-drop-target="{data: Visibility.Self, onDrop: SetVisibility}">
				<h2 class=vis-none :class="{working: loading}">Your Eyes Only</h2>
				<p>
					These links won’t be shown to anyone but you.  Others may still find
					you based on these but they’d have to already know.
				</p>
				<LinkList :links=none @remove="Remove" />
			</section>
			<section class="visibility friends" v-drop-target="{data: Visibility.Friends, onDrop: SetVisibility}">
				<h2 class=vis-friend :class="{working: loading}">Friends</h2>
				<p>These links are only shown to your friends.</p>
				<LinkList :links=friend @remove="Remove" />
			</section>
			<section class="visibility players" v-drop-target="{data: Visibility.Players, onDrop: SetVisibility}">
				<h2 class=vis-user :class="{working: loading}">Signed-in Players</h2>
				<p>These links are shown to anyone signed in.  This should exclude search engines and other bots.</p>
				<LinkList :links=user @remove="Remove" />
			</section>
			<section class="visibility everyone" v-drop-target="{data: Visibility.Everyone, onDrop: SetVisibility}">
				<h2 class=vis-all :class="{working: loading}">Everyone</h2>
				<p>These links are included on your profile no matter who’s looking.</p>
				<LinkList :links=everyone @remove="Remove" />
			</section>
			<section>
				<h2>Add Link</h2>
				<p>
					Add your personal website or profiles on other sites not integrated
					with LAN Ahead as links to share on your profile.  Just enter the URL
					below:
				</p>
				<form @submit.prevent=Add class=call-to-action>
					<label class=single-line-fields>
						<input v-model.trim=addUrl keyup.enter=AddFromEnterKey required placeholder=https://example.com/ type=url title="Enter the full URL of the link you want to add"/>
						<span class=validation :class=validation.status :title=validation.message></span>
						<button class=add :disabled=!canAddLink>Add</button>
					</label>
				</form>
			</section>
		</article>
	`
};
export default Links;
