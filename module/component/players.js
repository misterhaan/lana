import PlayerApi from "../api/player.js";

const Players = {
	props: [
		"view",
		"params",
		"player",
		"auths"
	],
	data() {
		return {
			players: [],
			loading: false
		};
	},
	created() {
		this.Load();
	},
	methods: {
		async Load() {
			this.loading = true;
			try {
				this.players = await PlayerApi.List();
			} finally {
				this.loading = false;
			}
		}
	},
	template: /*html*/ `
		<main>
			<h1>Players</h1>
			<p class=loading v-if=loading>Loading player list...</p>
			<ol class=playerList>
				<li v-for="player in players">
					<a :href="'#player/' + player.username">
						<img class=avatar :src=player.avatar>
						<div>{{player.username}}</div>
					</a>
				</li>
			</ol>
		</main>
	`
};
export default Players;
