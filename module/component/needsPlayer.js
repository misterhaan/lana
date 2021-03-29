const NeedsPlayer = {
	props: [
		"hasPlayer"
	],
	template: /*html*/ `
		<article v-if=!hasPlayer>
			<p>
				You’ll need to sign in before you can use this feature.
			</p>
		</article>
		<div v-else class=hasSignIn><slot></slot></div>
	`
};
export default NeedsPlayer;
