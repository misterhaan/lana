import SettingsApi from "../api/settings.js";
import Visibility from "../visibility.js";
import DragDrop from "../mixin/dragDrop.js";

const LinkList = {
	props: [
		"links"
	],
	mixins: [
		DragDrop
	],
	template: /* html */ `
		<ol>
			<li v-for="link in links" v-draggable="{name: link.name, data: link}">
				<a :href=link.url :class=link.type>{{link.name}}</a>
			</li>
		</ol>
	`
};

const Links = {
	data() {
		return {
			loading: false,
			links: null
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
				<LinkList :links=none />
			</section>
			<section class="visibility friends" v-drop-target="{data: Visibility.Friends, onDrop: SetVisibility}">
				<h2 class=vis-friend :class="{working: loading}">Friends</h2>
				<p>These links are only shown to your friends.</p>
				<LinkList :links=friend />
			</section>
			<section class="visibility players" v-drop-target="{data: Visibility.Players, onDrop: SetVisibility}">
				<h2 class=vis-user :class="{working: loading}">Signed-in Players</h2>
				<p>These links are shown to anyone signed in.  This should exclude search engines and other bots.</p>
				<LinkList :links=user />
			</section>
			<section class="visibility everyone" v-drop-target="{data: Visibility.Everyone, onDrop: SetVisibility}">
				<h2 class=vis-all :class="{working: loading}">Everyone</h2>
				<p>These links are included on your profile no matter who’s looking.</p>
				<LinkList :links=everyone />
			</section>
		</article>
	`
};
export default Links;
