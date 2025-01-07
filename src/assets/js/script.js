import "../sass/styles.sass";

const useEffect = wp.element.useState;
const useState = wp.element.useState;

const App = () => {
  const [mainDid, setMainDid] = useState("");
  const [domainName, setDomainName] = useState("");
  const [didSettings, setDidSettings] = useState([]);
  const [siteDomain, setSiteDomain] = useState("");

  useEffect(() => {
    /**
     * Initialize the options fields with the data received from the REST API
     * endpoint provided by the plugin.
     */
    wp.apiFetch({ path: "/react-settings-page/v1/options" }).then((data) => {
      setMainDid(data["didgeridoo_main_did"]);
      setDomainName(data["didgeridoo_subdomain"]);
      setSiteDomain(data["site_domain"]);

      // deserialize the didSettings
      const didSettingsObject = JSON.parse(data["didgeridoo_did_list"]);
      const didSettingsList = [];
      for (const [key, value] of Object.entries(didSettingsObject)) {
        didSettingsList.push({ name: key, did: value });
      }
      console.log(didSettingsObject);
      console.log(didSettingsList);
      setDidSettings(didSettingsList);
    });
  });

  const userHandleList = didSettings.map((setting, index) => {
    return (
      <div class="user-table__row">
        <input
          className="user-table__input"
          value={setting["name"]}
          onChange={(event) => {
            let newSettings = [...didSettings];
            newSettings[index] = {
              ...newSettings[index],
              name: event.target.value,
            };
            setDidSettings(newSettings);
          }}
        />
        <input
          className="user-table__input"
          value={setting["did"]}
          onChange={(event) => {
            let newSettings = [...didSettings];
            newSettings[index] = {
              ...newSettings[index],
              did: event.target.value,
            };
            setDidSettings(newSettings);
          }}
        />
        <button
          className="user-table__input button button-danger"
          onClick={() => {
            let newSettings = didSettings.filter((_, i) => i !== index);
            setDidSettings(newSettings);
          }}
        >
          Remove
        </button>
      </div>
    );
  });

  return (
    <div className="didgeridoo">
      <div className="ddoo__container">
        <div className="ddoo__row">
          <h1>DIDgeridoo Settings</h1>
        </div>

        <div className="ddoo__row ddoo__row--label">
          <h2>Main DID</h2>
        </div>

        <div className="ddoo__row">
          <input
            className="ddoo_field-single-input"
            value={mainDid}
            onChange={(event) => {
              setMainDid(event.target.value);
            }}
          />
        </div>

        <div className="ddoo__row ddoo__row--label">
          <h2>Subdomain</h2>
        </div>

        <div className="ddoo__row">
          <label>cool-username.</label>
          <input
            className="ddoo_field-single-input"
            value={domainName}
            onChange={(event) => {
              setDomainName(event.target.value);
            }}
          />
          <label>.{siteDomain}</label>
        </div>

        <div className="ddoo__row ddoo__row--label">
          <h2>DID User Handle Settings</h2>
        </div>

        <div className="ddoo__row ddoo__row--short-spacing">
          <div className="user-table">
            <div class="user-table__row user-table__row--header">
              <label>User Handle</label>
              <label>DID</label>
            </div>
            <div class="user-table__body">
            {userHandleList}
            </div>
          </div>
        </div>

        <div className="ddoo__row ddoo__row--centered">
          <div>
            <button
              className="button button-primary"
              onClick={() => {
                setDidSettings([...didSettings, { name: "", did: "" }]);
              }}
            >
              Add User
            </button>
          </div>
        </div>

        <div className="ddoo__row">
          <button
            className="button button-primary"
            onClick={() => {
              // convert didSettings from array to key value pair
              let didSettingsObject = {};
              didSettings.forEach((setting) => {
                didSettingsObject[setting["name"]] = setting["did"];
              });

              // json serialize the didSettings
              const didSettingsJson = JSON.stringify(didSettingsObject);
              console.log(didSettingsJson);

              wp.apiFetch({
                path: "/react-settings-page/v1/options",
                method: "POST",
                data: {
                  didgeridoo_main_did: mainDid,
                  didgeridoo_subdomain: domainName,
                  didgeridoo_did_list: didSettingsJson,
                },
              }).then((data) => {
                alert("Options saved successfully!");
              });
            }}
          >
            Save
          </button>
        </div>
      </div>
    </div>
  );
};

document.addEventListener("DOMContentLoaded", () => {
  const root = document.getElementById("name-id-list");
  if (root) {
    ReactDOM.render(<App />, root);
  }
});
