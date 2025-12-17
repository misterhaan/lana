import PlayerApi from "../api/player.js";

const Player = {
	props: [
		"view",
		"params",
		"player",
		"auths"
	],
	data() {
		return {
			profile: null,
			loading: false
		};
	},
	created() {
		if(this.view?.Name)
			this.Load();
		else if(this.player)
			location = "#player/" + this.player.username;
		else
			location = "#players";
	},
	methods: {
		async Load() {
			this.loading = true;
			try {
				this.profile = await PlayerApi.Profile(this.view?.Name);
			} finally {
				this.loading = false;
			}
		}
	},
	template: /*html*/ `
		<main>
			<h1 v-if=!profile>Player Profile</h1>
			<header v-if=profile class=profile>
				<img class=avatar :src=profile.avatar alt="">
				<div>
					<h1>{{profile.username}}</h1>
					<p>Joined <time :datetime=profile.joined.datetime :tooltip=profile.joined.tooltip>{{profile.joined.display}} ago</time></p>
				</div>
			</header>
			<section v-if=profile?.links class=links>
				<a v-for="link in profile.links" :href=link.url :class=link.type>{{link.name}}</a>
			</section>
			<p class=loading v-if=loading>Loading player profile for {{view.Name}}...</p>
		</main>
	`
};
export default Player;
