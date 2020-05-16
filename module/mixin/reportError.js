export default {
	methods: {
		Error(error) {
			if(typeof error == "string")
				error = new Error(error);
			this.$emit("error", error);
		}
	}
}
