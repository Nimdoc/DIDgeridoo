import "../sass/styles.sass";
import PopupModal from "./PopupModal";

const { __, _x, _n, sprintf } = wp.i18n;

const useEffect = wp.element.useState;
const useState = wp.element.useState;

const App = () => {
  const [mainDid, setMainDid] = useState("");
  const [domainName, setDomainName] = useState("");
  const [didSettings, setDidSettings] = useState([]);
  const [siteDomain, setSiteDomain] = useState("");
  const [enableOrgMode, setEnableOrgMode] = useState(false);
  const [errors, setErrors] = useState({});
  const [successMessages, setSuccessMessages] = useState({});

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
        setEnableOrgMode(data["didgeridoo_enable_org_mode"] == "1");

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

  const successMessagesList = Object.keys(successMessages).map((key) => {
    return (
      <div className="ddoo__row ddoo__row--success">
        <p>{successMessages[key]}</p>
        <button
          className="ddoo__button-close ddoo__button-close--success notice-dismiss"
          onClick={() => {
            let newSuccessMessages = { ...successMessages };
            delete newSuccessMessages[key];
            setSuccessMessages(newSuccessMessages);
          }}
        >
        </button>
      </div>
    );
  });

  const sortDidSettings = (sortBy) => {
    if (sortBy === "name_asc") {
      didSettings.sort((a, b) => {
        return a.name.localeCompare(b.name);
      });
    } else if (sortBy === "name_desc") {
      didSettings.sort((a, b) => {
        return b.name.localeCompare(a.name);
      });
    } else if (sortBy === "date_asc") {
      didSettings.sort((a, b) => {
        return new Date(a.last_updated) - new Date(b.last_updated);
      });
    } else if (sortBy === "date_desc") {
      didSettings.sort((a, b) => {
        return new Date(b.last_updated) - new Date(a.last_updated);
      });
    }

    setDidSettings([...didSettings]);
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
          <div class="user-table__col user-table__col--name">
            <div class="user-table__label">
              <label>{__("User Handle", "didgeridoo")}</label>
            </div>
            {hasNameErrors && (
              <div class="user-table__error-list">
                <ul>{nameErrorList}</ul>
              </div>
            )}
            <input
              className={`user-table__input ${
                hasNameErrors && "user-table__input--error"
              }`}
              type="text"
              disabled={!enableOrgMode}
              value={setting["name"]}
              onChange={(event) => {
                let newSettings = [...didSettings];
                newSettings[index] = {
                  ...newSettings[index],
                  name: event.target.value,
                  last_updated: new Date().toLocaleString(),
                };
                setDidSettings(newSettings);
              }}
            />
          </div>
          <div class="user-table__col user-table__col--did">
            <div class="user-table__label">
              <label>{__("DID", "didgeridoo")}</label>
            </div>
            {hasDidErrors && (
              <div class="user-table__error-list">
                <ul>{didErrorList}</ul>
              </div>
            )}
            <input
              className={`user-table__input ${
                hasDidErrors && "user-table__input--error"
              }`}
              type="text"
              disabled={!enableOrgMode}
              value={setting["did"]}
              onChange={(event) => {
                let newSettings = [...didSettings];
                newSettings[index] = {
                  ...newSettings[index],
                  did: event.target.value,
                  last_updated: new Date().toLocaleString(),
                };
                setDidSettings(newSettings);
              }}
            />
          </div>
          <div class="user-table__col user-table__last-updated">
            <div class="user-table__label">
              <label>{__('Last Updated', 'didgeridoo')}</label>
            </div>
            {setting["last_updated"] ? setting["last_updated"] : "-"}
          </div>
          <div class="user-table__col">
            <button
              className="user-table__input button button-danger"
              disabled={!enableOrgMode}
              onClick={() => {
                let newSettings = didSettings.filter((_, i) => i !== index);
                setDidSettings(newSettings);
              }}
            >
              {__("Remove", "didgeridoo")}
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
          <h1>{__('DIDgeridoo Settings', 'didgeridoo')}</h1>
        </div>

        {successMessagesList}

        <div className="ddoo__row ddoo__row--label">
          <h2>{__('Main DID', 'didgeridoo')}</h2>
          <PopupModal title={__('Main DID', 'didgeridoo')}>
            <p>{__('This is the main DID that will be used for the subdomain.', 'didgeridoo')}</p>
          </PopupModal>
        </div>

        {errors["didgeridoo_main_did"] && (
          <div className="ddoo__row ddoo__row--error">
            <ul>
              {errors["didgeridoo_main_did"].map((error) => {
                return <li>{error}</li>;
              })}
            </ul>
            <button
              className="ddoo__button-close ddoo__button-close--error notice-dismiss"
              onClick={() => {
                removeError("didgeridoo_main_did");
              }}
            >
            </button>
          </div>
        )}

        <div className="ddoo__row">
          <input
            className="ddoo_field-single-input"
            type="text"
            value={mainDid}
            onChange={(event) => {
              setMainDid(event.target.value);
            }}
          />
        </div>

        <div className="ddoo__row ddoo__row--label">
          <h2>{__('Enable Organization Mode', 'didgeridoo')}</h2>
          <PopupModal title={__('Organization Mode', 'didgeridoo')}>
            <p>{__('This will enable the organization mode for the DIDgeridoo plugin.', 'didgeridoo')}</p>
          </PopupModal>
        </div>

        <div className="ddoo__row">
          <input
            type="checkbox"
            checked={enableOrgMode}
            onChange={(event) => {
              setEnableOrgMode(event.target.checked);
            }}
          />
        </div>

        <div className="ddoo__row ddoo__row--label">
          <h2>{__('Subdomain', 'didgeridoo')}</h2>
          <PopupModal title={__('Subdomain', 'didgeridoo')}>
            <p>{__('This is the subdomain that will be used to generate the user handles.', 'didgeridoo')}</p>
          </PopupModal>
        </div>

        {errors["didgeridoo_subdomain"] && (
          <div className="ddoo__row ddoo__row--error">
            <ul>
              {errors["didgeridoo_subdomain"].map((error) => {
                return <li>{error}</li>;
              })}
            </ul>
            <button
              className="ddoo__button-close ddoo__button-close--error notice-dismiss"
              onClick={() => {
                removeError("didgeridoo_subdomain");
              }}
            >
            </button>
          </div>
        )}

        <div className="ddoo__row">
          <label>{__('cool-username.', 'didgeridoo')}</label>
          <input
            className="ddoo_field-single-input"
            type="text"
            disabled={!enableOrgMode}
            value={domainName}
            onChange={(event) => {
              setDomainName(event.target.value);
            }}
          />
          <label>.{siteDomain}</label>
        </div>

        <div className="ddoo__row ddoo__row--label ddoo__row--handle-settings">
          <div className="ddoo__row-header">
            <h2>{__('DID User Handle Settings', 'didgeridoo')}</h2>
            <PopupModal title={__('DID User Handle Settings', 'didgeridoo')}>
              <p>{__('These are the user handles that will be generated for the DID.', 'didgeridoo')}</p>
            </PopupModal>
          </div>
          <div className="ddoo__sort">
            <label htmlFor="sort">{__('Sort by', 'didgeridoo')}</label>
            <select onChange={(event) => sortDidSettings(event.target.value)}>
              <option value="name_asc" selected>{__('Sort by Name Asc.', 'didgeridoo')}</option>
              <option value="name_desc">{__('Sort by Name Desc.', 'didgeridoo')}</option>
              <option value="date_asc">{__('Sort by Date Asc.', 'didgeridoo')}</option>
              <option value="date_desc">{__('Sort by Date Desc.', 'didgeridoo')}</option>
            </select>
          </div>
        </div>

        {errors["didgeridoo_did_list"] && (
          <div className="ddoo__row ddoo__row--error">
            <ul>
              {errors["didgeridoo_did_list"].map((error) => {
                return <li>{error}</li>;
              })}
            </ul>
            <button
              className="ddoo__button-close ddoo__buton-close--error notice-dismiss"
              onClick={() => {
                removeError("didgeridoo_did_list");
              }}
            >
            </button>
          </div>
        )}

        <div className="ddoo__row ddoo__row--short-spacing">
          <div className="user-table">
            <div class="user-table__row user-table__row--header">
              <label>{__('User Handle', 'didgeridoo')}</label>
              <label>{__('DID', 'didgeridoo')}</label>
              <label>{__('Last Updated', 'didgeridoo')}</label>
            </div>
            <div class="user-table__body">{userHandleList}</div>
          </div>
        </div>

        <div className="ddoo__row ddoo__row--centered">
          <div>
            <button
              className="button button-primary"
              disabled={!enableOrgMode}
              onClick={() => {
                setDidSettings([...didSettings, { name: "", did: "" }]);
              }}
            >
              {__('Add User', 'didgeridoo')}
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
                  didgeridoo_enable_org_mode: enableOrgMode,
                },
              })
                .then((data) => {
                  setErrors({});
                  setSuccessMessages({ success: data});
                })
                .catch((error) => {
                  setErrors(error);
                  setSuccessMessages({});
                });
            }}
          >
            {__('Save', 'didgeridoo')}
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
