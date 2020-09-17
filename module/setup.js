import Vue from "../external/vue.esm.browser.min.js";
import AppName from "./appName.js";
import TitleBar from "./component/titlebar.js";
import StatusBar from "./component/statusbar.js";
import SetupApi from "./api/setup.js";
import ReportError from "./mixin/reportError.js";


const SetupLevel = {
	Unknown: -99,
	FreshInstall: -4,
	DatabaseConnectionDefined: -3,
	DatabaseExists: -2,
	DatabaseInstalled: -1,
	DatabaseUpToDate: 0
};

const SetupStep = {
	methods: {
		...ReportError.methods,
		...{
			Recheck(minLevel, successMessage, errorMessage) {
				this.checking = true;
				SetupApi.Level().done(result => {
					if(result.level >= minLevel) {
						this.$emit("log-step", successMessage);
						this.$emit("set-level", result);
					} else
						this.Error(errorMessage);
				}).fail(this.Error).always(() => {
					this.checking = false;
				});
			}
		}
	}
};

new Vue({
	el: "#lana",
	data: {
		level: SetupLevel.Unknown,
		stepData: false,
		stepsTaken: [],
		error: false
	},
	computed: {
		progress() {
			switch(this.level) {
				case SetupLevel.Unknown: return { percent: 0, component: "checkingInstall" };
				case SetupLevel.FreshInstall: return { percent: 0, component: "defineDatabase" };
				case SetupLevel.DatabaseConnectionDefined: return { percent: 25, component: "createDatabase" };
				case SetupLevel.DatabaseExists: return { percent: 50, component: "installDatabase" };
				case SetupLevel.DatabaseInstalled: return { percent: 75, component: "upgradeDatabase" };
				case SetupLevel.DatabaseUpToDate: return { percent: 100, component: "setupComplete" };
			}
		}
	},
	created() {
		SetupApi.Level().done(result => {
			this.level = result.level;
			this.stepData = result.stepData;
		}).fail(error => {
			this.error = error;
		});
	},
	components: {
		titlebar: TitleBar,
		statusbar: StatusBar,
		checkingInstall: {
			template: /*html*/ `
				<article>
					<h2>Initializing</h2>
					<p class=working>Checking current setup progress</p>
				</article>
			`
		},
		defineDatabase: {
			props: [
				"stepData"
			],
			data() {
				return {
					host: "localhost",
					name: "lana",
					user: "",
					pass: "",
					showPass: false,
					manual: false,
					saving: false,
					checking: false
				};
			},
			computed: {
				hasAllRequiredFields() {
					return !this.working && this.host && this.name && this.user && this.pass;
				}
			},
			methods: {
				Save() {
					this.saving = true;
					SetupApi.ConfigureDatabase(this.host, this.name, this.user, this.pass).done(result => {
						if(result.saved) {
							this.$emit("log-step", "Saved database connection configuration to " + result.path);
							this.$emit("set-level", result);
						} else
							this.manual = { path: result.path, contents: result.contents, reason: result.message };
					}).fail(this.Error).always(() => {
						this.saving = false;
					});
				}
			},
			mixins: [SetupStep],
			template: /*html*/ `
				<article>
					<h2>Define Database Connection</h2>
					<p>
						${AppName.Full} stores data in a MySQL database.  Enter the
						connection details below and they will be saved to the appropriate
						location, provided the web server can write there.
					</p>
					<section class=singlelinefields id=dbconn>
						<label title="Enter the hostname for the database.  Usually the database is the same host as the web server, and the hostname should be 'localhost'">
							<span class=label>Host:</span>
							<input v-model.trim=host required>
						</label>
						<label title="Enter the name of the database ${AppName.Short} should use">
							<span class=label>Database:</span>
							<input v-model.trim=name required>
						</label>
						<label title="Enter the username that owns the ${AppName.Short} database">
							<span class=label>Username:</span>
							<input v-model.trim=user required>
						</label>
						<label title="Enter the password for the user that owns the ${AppName.Short} database">
							<span class=label>Password:</span>
							<input :type="showPass ? 'text' : 'password'" v-model=pass required>
							<button :class="showPass ? 'hide' : 'show'" :title="showPass ? 'Hide the password' : 'Show the password'" @click.prevent="showPass = !showPass"><span>{{showPass ? "hide" : "show"}}</span></button>
						</label>
						<nav class=calltoaction><button :disabled=!hasAllRequiredFields :class="{working: saving}" @click.prevent=Save title="Save database connection configuration">Save</button></nav>
					</section>
					<section v-if=manual>
						<h3>Unable to Save Database Connection Configuration</h3>
						<details>
							<summary>
								${AppName.Short} couldn’t save the database connection
								configuration to file.
							</summary>
							<blockquote><p>{{manual.reason}}</p></blockquote>
						</details>
						<p>
							You can either address the issue or save the following text into
							<code>{{manual.path}}</code>
						</p>
						<pre><code>{{manual.contents}}</code></pre>
						<nav class=calltoaction><button :disabled=checking :class="{working: checking}" @click.prevent="Recheck(${SetupLevel.DatabaseConnectionDefined}, 'Confirmed database connection configuration file exists', 'Database connection configuration file not found.  Did you create it in the correct path?')" title="Check if ${AppName.Short} can read the database connection configuration">Continue</button></nav>
					</section>
				</article>
			`
		},
		createDatabase: {
			props: [
				"stepData"
			],
			data() {
				return {
					checking: false
				};
			},
			mixins: [SetupStep],
			template: /*html*/ `
				<article>
					<h2>Create Database</h2>
					<details>
						<summary>${AppName.Short} can’t connect to the database.</summary>
						<blockquote><p>{{stepData.error}}</p></blockquote>
					</details>
					<p>
						This usually means the database hasn’t been created yet.  The
						following statements run as the MySQL root user will create the
						database and grant access to the appropriate MySQL user and
						password.
					</p>
					<pre><code>create database if not exists \`{{stepData.name}}\` character set utf8mb4 collate utf8mb4_unicode_ci;
grant all on \`{{stepData.name}}\`.* to '{{stepData.user}}'@'localhost' identified by '{{stepData.pass}}';</code></pre>
					<p>
						By default MySQL on Linux allows root access with this command as
						a user with sudo permission:  <code>sudo mysql -u root</code> and
						paste the above statements followed by <code>exit</code> to get
						back to the Linux command line.
					</p>
					<nav class=calltoaction><button :disabled=checking :class="{working: checking}" @click.prevent="Recheck(${SetupLevel.DatabaseExists}, 'Confirmed database exists', 'Cannot access database.  Did you create it and grant access for the configured user?')" title="Check if ${AppName.Short} has a database and can access it">Continue</button></nav>
				</article>
			`
		},
		installDatabase: {
			props: [
				"stepData"
			],
			data() {
				return {
					working: false
				};
			},
			created() {
				this.Install();
			},
			methods: {
				Install() {
					this.working = true;
					SetupApi.InstallDatabase().done(() => {
						this.$emit("log-step", "Installed new database");
						this.$emit("set-level", { level: SetupLevel.DatabaseUpToDate, stepData: false });
					}).fail(this.Error).always(() => {
						this.working = false;
					});
				}
			},
			mixins: [ReportError],
			template: /*html*/ `
				<article>
					<h2>Install Database</h2>
					<p v-if=working class=loading>Installing a new database...</p>
					<nav class=calltoaction v-if=!working><button @click.prevent=Install title="Try installing the ${AppName.Short} database again">Try Again</button></nav>
				</article>
			`
		},
		upgradeDatabase: {
			props: [
				"stepData"
			],
			data() {
				return {
					working: false
				};
			},
			created() {
				this.Upgrade();
			},
			methods: {
				Upgrade() {
					this.working = true;
					SetupApi.UpgradeDatabase().done(result => {
						this.$emit("log-step", "Upgraded database");
						this.$emit("set-level", { level: SetupLevel.DatabaseUpToDate, stepData: false });
					}).fail(this.Error).always(() => {
						this.working = false;
					});
				}
			},
			mixins: [ReportError],
			template: /*html*/ `
				<article>
					<h2>Upgrade Database</h2>
					<p v-if=stepData.structureBehind>Database structure is {{stepData.structureBehind}} version{{stepData.structureBehind > 1 ? "s" : ""}} behind.</p>
					<p v-if=stepData.dataBehind>Data is {{stepData.dataBehind}} version{{stepData.dataBehind > 1 ? "s" : ""}} behind.</p>
					<p v-if=working class=loading>Upgrading...</p>
					<nav class=calltoaction v-if=!working><button @click.prevent=Upgrade title="Try upgrading the ${AppName.Short} database again">Try Again</button></nav>
				</article>
			`
		},
		setupComplete: {
			props: [
				"stepData"
			],
			template: /*html*/ `
				<article>
					<h2>Complete!</h2>
					<p>
						Setup has completed and ${AppName.Full} is ready for use.
					</p>
					<nav class=calltoaction><a href=.>Enter ${AppName.Full}</a></nav>
				</article>
			`
		}
	},
	template: /*html*/ `
		<div id=lana>
			<titlebar></titlebar>
			<main>
				<h1>Setup</h1>
				<div class=percentfield><div class=percentvalue :style="{width: progress.percent + '%'}"></div></div>
				<ol class=stepsTaken v-if=stepsTaken.length v-for="step in stepsTaken">
					<li>{{step}}</li>
				</ol>
				<component :is=progress.component :step-data=stepData @set-level="level = $event.level; stepData = $event.stepData" @log-step="stepsTaken.push($event)" @error="error = $event"></component>
			</main>
			<statusbar :last-error=error></statusbar>
		</div>
`
});
