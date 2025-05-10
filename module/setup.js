import { createApp } from "../external/vue.esm-browser.prod.js";
import AppName from "./appName.js";
import TitleBar from "./component/titlebar.js";
import StatusBar from "./component/statusbar.js";
import SetupApi from "./api/setup.js";
import ReportError from "./mixin/reportError.js";

const KeyClassRegex = /class ([A-Za-z]+) \{/;
const KeyValueRegex = /const ([A-Za-z]+) = '';/;

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

createApp({
	name: "LanaSetup",
	data() {
		return {
			level: SetupLevel.Unknown,
			stepData: false,
			stepsTaken: [],
			dbInfo: false,
			error: false
		};
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
	template: /*html*/ `
		<div id=lana>
			<titlebar></titlebar>
			<main>
				<h1>Setup</h1>
				<div class=percentfield><div class=percentvalue :style="{width: progress.percent + '%'}"></div></div>
				<ol class=stepsTaken v-if=stepsTaken.length v-for="step in stepsTaken">
					<li>{{step}}</li>
				</ol>
				<component :is=progress.component :step-data=stepData :db-info=dbInfo @set-db-info="dbInfo = $event" @set-level="level = $event.level; stepData = $event.stepData" @log-step="stepsTaken.push($event)" @error="error = $event"></component>
			</main>
			<statusbar :last-error=error></statusbar>
		</div>
`
}).component("titlebar", TitleBar)
	.component("statusbar", StatusBar)
	.component("checkingInstall", {
		template: /*html*/ `
			<article>
				<h2>Initializing</h2>
				<p class=working>Checking current setup progress</p>
			</article>
		`
	}).component("defineDatabase", {
		props: [
			"stepData"
		],
		data() {
			return {
				database: {
					host: "localhost",
					name: "lana",
					user: "",
					pass: "",
					showPass: false
				},
				twitch: {
					id: "",
					secret: "",
					showSecret: false
				},
				manual: false,
				saving: false,
				checking: false
			};
		},
		computed: {
			hasAllRequiredFields() {
				return !this.working && this.database.host && this.database.name && this.database.user && this.database.pass && this.twitch.id && this.twitch.secret;
			},
			contents() {
				if(this.manual && this.manual.template) {
					const lines = this.manual.template.split("\n");
					let keyClass = "";
					for(const i in lines) {
						const classMatch = KeyClassRegex.exec(lines[i]);
						if(classMatch)
							keyClass = classMatch[1];
						else if(keyClass) {
							const valueMatch = KeyValueRegex.exec(lines[i]);
							if(valueMatch) {
								switch(keyClass) {
									case "KeysDB":
										switch(valueMatch[1]) {
											case "Host":
												lines[i] = lines[i].replace("''", `'${this.database.host}'`);
												break;
											case "Name":
												lines[i] = lines[i].replace("''", `'${this.database.name}'`);
												break;
											case "User":
												lines[i] = lines[i].replace("''", `'${this.database.user}'`);
												break;
											case "Pass":
												lines[i] = lines[i].replace("''", `'${this.database.pass}'`);
												break;
										}
										break;
									case "KeysTwitch":
										switch(valueMatch[1]) {
											case "ClientId":
												lines[i] = lines[i].replace("''", `'${this.twitch.id}'`);
												break;
											case "ClientSecret":
												lines[i] = lines[i].replace("''", `'${this.twitch.secret}'`);
												break;
										}
										break;
								}
							}
						}
					}
					return lines.join("\n");
				}
				return '';
			}
		},
		methods: {
			Save() {
				this.saving = true;
				SetupApi.ConfigureConnections(this.database.host, this.database.name,
					this.database.user, this.database.pass, this.twitch.id, this.twitch.secret).done(result => {
						if(result.saved) {
							this.$emit("log-step", "Saved database connection configuration to " + result.path);
							this.$emit("set-db-info", { name: this.name, user: this.user, pass: this.pass });
							this.$emit("set-level", result);
						} else
							this.manual = { path: result.path, template: result.template, reason: result.message };
					}).fail(this.Error).always(() => {
						this.saving = false;
					});
			}
		},
		mixins: [SetupStep],
		template: /*html*/ `
			<article>
				<h2>Define Connections</h2>
				<p>
					${AppName.Full} stores data in a MySQL database and interacts with
					other websites.  Enter the connection details below and they will
					be saved to the appropriate location, provided the web server can
					write there.
				</p>
				<section class=singlelinefields id=dbconn>
					<h3>Database</h3>
					<label title="Enter the hostname for the database.  Usually the database is the same host as the web server, and the hostname should be 'localhost'">
						<span class=label>Host:</span>
						<input v-model.trim=database.host required>
					</label>
					<label title="Enter the name of the database ${AppName.Short} should use">
						<span class=label>Database:</span>
						<input v-model.trim=database.name required>
					</label>
					<label title="Enter the username that owns the ${AppName.Short} database">
						<span class=label>Username:</span>
						<input v-model.trim=database.user required>
					</label>
					<label title="Enter the password for the user that owns the ${AppName.Short} database">
						<span class=label>Password:</span>
						<input :type="database.showPass ? 'text' : 'password'" v-model=database.pass required>
						<button :class="database.showPass ? 'hide' : 'show'" :title="database.showPass ? 'Hide the password' : 'Show the password'" @click.prevent="database.showPass = !database.showPass"><span>{{database.showPass ? "hide" : "show"}}</span></button>
					</label>
				</section>
				<section class=singlelinefields id=twitchkeys>
					<h3>Twitch</h3>
					<label title="Enter the client ID for this website as set up in Twitch">
						<span class=label>Client ID:</span>
						<input v-model.trim=twitch.id required>
					</label>
					<label title="Enter the client secret for this website as set up in Twitch (may need to generate a new one)">
						<span class=label>Secret:</span>
						<input v-model.trim=twitch.secret :type="twitch.showSecret ? 'text' : 'password'" required>
						<button :class="twitch.showSecret ? 'hide' : 'show'" :title="twitch.showSecret ? 'Hide the secret' : 'Show the secret'" @click.prevent="twitch.showSecret = !twitch.showSecret"><span>{{twitch.showSecret ? "hide" : "show"}}</span></button>
					</label>
				</section>
				<nav class=calltoaction><button :disabled=!hasAllRequiredFields :class="{working: saving}" @click.prevent=Save title="Save connection configuration">Save</button></nav>
				<section v-if=manual>
					<h3>Unable to Save Connection Configuration</h3>
					<details>
						<summary>
							${AppName.Short} couldn’t save the connection configuration to file.
						</summary>
						<blockquote><p>{{manual.reason}}</p></blockquote>
					</details>
					<p>
						You can either address the issue and try again, or save the
						following text into
						<code>{{manual.path}}</code>
					</p>
					<pre><code>{{contents}}</code></pre>
					<nav class=calltoaction><button :disabled=checking :class="{working: checking}" @click.prevent="Recheck(${SetupLevel.DatabaseConnectionDefined}, 'Confirmed database connection configuration file exists', 'Database connection configuration file not found.  Did you create it in the correct path?')" title="Check if ${AppName.Short} can read the database connection configuration">Continue</button></nav>
				</section>
			</article>
		`
	}).component("createDatabase", {
		props: [
			"stepData",
			"dbInfo"
		],
		data() {
			return {
				checking: false
			};
		},
		computed: {
			name() {
				return dbInfo && dbInfo.name || "DATABASE";
			},
			user() {
				return dbInfo && dbInfo.user || "USER";
			},
			pass() {
				return dbInfo && dbInfo.pass || "PASSWORD";
			}
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
					password.  You may need to change 'localhost' if MySQL is on a
					different server than the web server.
				</p>
				<pre><code>create database if not exists \`{{name}}\` character set utf8mb4 collate utf8mb4_unicode_ci;
grant all on \`{{name}}\`.* to '{{user}}'@'localhost' identified by '{{pass}}';</code></pre>
				<p>
					By default MySQL on Linux allows root access with this command as
					a user with sudo permission:  <code>sudo mysql -u root</code> and
					paste the above statements followed by <code>exit</code> to get
					back to the Linux command line.
				</p>
				<nav class=calltoaction><button :disabled=checking :class="{working: checking}" @click.prevent="Recheck(${SetupLevel.DatabaseExists}, 'Confirmed database exists', 'Cannot access database.  Did you create it and grant access for the configured user?')" title="Check if ${AppName.Short} has a database and can access it">Continue</button></nav>
			</article>
		`
	}).component("installDatabase", {
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
	}).component("upgradeDatabase", {
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
	}).component("setupComplete", {
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
	}).mount("#lana");
