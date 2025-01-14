import "../sass/styles.sass";

const useEffect = wp.element.useState;
const useState = wp.element.useState;

const App = () => {
  const [mainDid, setMainDid] = useState("");
  const [domainName, setDomainName] = useState("");
  const [didSettings, setDidSettings] = useState([]);
  const [siteDomain, setSiteDomain] = useState("");
  const [errors, setErrors] = useState({});

  useEffect(() => {
    /**
     * Initialize the options fields with the data received from the REST API
     * endpoint provided by the plugin.
     */
    wp.apiFetch({ path: "/react-settings-page/v1/options" })
      .then((data) => {
        setMainDid(data["didgeridoo_main_did"]);
        setDomainName(data["didgeridoo_subdomain"]);
        setSiteDomain(data["site_domain"]);

        // deserialize the didSettings
        const didSettingsList = JSON.parse(data["didgeridoo_did_list"]);
        setDidSettings(didSettingsList);
      })
      .catch((error) => {
        console.error(error);
      });
  });

  const removeError = (key) => {
    let newErrors = { ...errors };
    delete newErrors[key];
    setErrors(newErrors);
  };

  const userHandleList = didSettings.map((setting, index) => {
    const nameErrors = errors[index + ".name"] ? errors[index + ".name"] : [];
    const didErrors = errors[index + ".did"] ? errors[index + ".did"] : [];

    const nameErrorList = nameErrors.map((error) => {
      return <li>{error}</li>;
    });

    const didErrorList = didErrors.map((error) => {
      return <li>{error}</li>;
    });

    const hasNameErrors = nameErrors.length > 0;
    const hasDidErrors = didErrors.length > 0;

    return (
      <>
        <div class="user-table__row">
          <div class="user-table__col">
            {hasNameErrors && (
              <div class="user-table__error-list">
                <ul>{nameErrorList}</ul>
              </div>
            )}
            <input
              className={`user-table__input ${
                hasNameErrors && "user-table__input--error"
              }`}
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
          </div>
          <div class="user-table__col">
            {hasDidErrors && (
              <div class="user-table__error-list">
                <ul>{didErrorList}</ul>
              </div>
            )}
            <input
              className={`user-table__input ${
                hasDidErrors && "user-table__input--error"
              }`}
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
          </div>
          <div class="user-table__col">
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
        </div>
      </>
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

        {errors["didgeridoo_main_did"] && (
          <div className="ddoo__row ddoo__row--error">
            <ul>
              {errors["didgeridoo_main_did"].map((error) => {
                return <li>{error}</li>;
              })}
            </ul>
            <button
              className="ddoo__row--error__close"
              onClick={() => {
                removeError("didgeridoo_main_did");
              }}
            >
              X
            </button>
          </div>
        )}

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

        {errors["didgeridoo_subdomain"] && (
          <div className="ddoo__row ddoo__row--error">
            <ul>
              {errors["didgeridoo_subdomain"].map((error) => {
                return <li>{error}</li>;
              })}
            </ul>
            <button
              className="ddoo__row--error__close"
              onClick={() => {
                removeError("didgeridoo_subdomain");
              }}
            >
              X
            </button>
          </div>
        )}

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

        {errors["didgeridoo_did_list"] && (
          <div className="ddoo__row ddoo__row--error">
            <ul>
              {errors["didgeridoo_did_list"].map((error) => {
                return <li>{error}</li>;
              })}
            </ul>
            <button
              className="ddoo__row--error__close"
              onClick={() => {
                removeError("didgeridoo_did_list");
              }}
            >
              X
            </button>
          </div>
        )}

        <div className="ddoo__row ddoo__row--short-spacing">
          <div className="user-table">
            <div class="user-table__row user-table__row--header">
              <label>User Handle</label>
              <label>DID</label>
            </div>
            <div class="user-table__body">{userHandleList}</div>
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
              // json serialize the didSettings
              const didSettingsJson = JSON.stringify(didSettings);

              wp.apiFetch({
                path: "/react-settings-page/v1/options",
                method: "POST",
                data: {
                  didgeridoo_main_did: mainDid,
                  didgeridoo_subdomain: domainName,
                  didgeridoo_did_list: didSettingsJson,
                },
              })
                .then((data) => {
                  alert("Options saved successfully!");
                })
                .catch((error) => {
                  setErrors(error);
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
