/**
 * ******************************************************************************
 * Copyright (c) 2025 Tom Busby
 *
 * This program and the accompanying materials are made available under the
 * terms of the Eclipse Public License 2.0 which is available at
 * http://www.eclipse.org/legal/epl-2.0.
 *
 * This Source Code may also be made available under the following Secondary
 * Licenses when the conditions for such availability set forth in the Eclipse
 * Public License, v. 2.0 are satisfied: GNU General Public License, version 2
 * with the GNU Classpath Exception which is
 * available at https://www.gnu.org/software/classpath/license.html.
 *
 * SPDX-License-Identifier: EPL-2.0 OR GPL-2.0 WITH Classpath-exception-2.0
 * ******************************************************************************
 */

import { createRoot } from "@wordpress/element";

import "../sass/styles.sass";
import PopupModal from "./PopupModal";

const { __, _x, _n, sprintf } = wp.i18n;

const useEffect = wp.element.useState;
const useState = wp.element.useState;

const App = () => {
  const [mainDid, setMainDid] = useState("");
  const [domainName, setDomainName] = useState("");
  const [siteDomain, setSiteDomain] = useState("");
  const [enableOrgMode, setEnableOrgMode] = useState(false);
  const [errors, setErrors] = useState({});
  const [successMessages, setSuccessMessages] = useState({});

  useEffect(() => {
    /**
     * Initialize the options fields with the data received from the REST API
     * endpoint provided by the plugin.
     */
    wp.apiFetch({ path: "/didgeridoo/v1/options" })
      .then((data) => {
        setMainDid(data["didgeridoo_main_did"]);
        setDomainName(data["didgeridoo_subdomain"]);
        setSiteDomain(data["site_domain"]);
        setEnableOrgMode(data["didgeridoo_enable_org_mode"] == "1");
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
        ></button>
      </div>
    );
  });

  return (
    <div className="didgeridoo">
      <div className="ddoo__container">
        <div className="ddoo__row">
          <h1>{__("DIDgeridoo Settings", "didgeridoo")}</h1>
        </div>

        {successMessagesList}

        <div className="ddoo__row ddoo__row--label">
          <h2>{__("Main DID", "didgeridoo")}</h2>
          <PopupModal title={__("Main DID", "didgeridoo")}>
            <p>
              {__(
                "This is the main DID that will be used for the domain (Site Address in the settings).",
                "didgeridoo"
              )}
            </p>
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
            ></button>
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
          <h2>{__("Enable Organization Mode", "didgeridoo")}</h2>
          <PopupModal title={__("Organization Mode", "didgeridoo")}>
            <p>
              {__(
                "This will enable adding multiple user handles in addition to you main website handle. NOTE: You must have a wildcard A record pointed to this Wordpress instance in order to use this feature. Read more about configuring DNS for ATProto below.",
                "didgeridoo"
              )}
            </p>
            <p>
              <a
                href="https://atproto.com/guides/self-hosting#configure-dns-for-your-domain"
                target="_blank"
              >
                https://atproto.com/guides/self-hosting#configure-dns-for-your-domain
              </a>
            </p>
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
          <h2>{__("Subdomain", "didgeridoo")}</h2>
          <PopupModal title={__("Subdomain", "didgeridoo")}>
            <p>
              {__(
                "Here you may add more labels between your domain and user handle name. You may also leave this field blank. Read more about ATProto handles below.",
                "didgeridoo"
              )}
            </p>
            <p>
              <a href="https://atproto.com/specs/handle" target="_blank">
                https://atproto.com/specs/handle
              </a>
            </p>
          </PopupModal>
          <button
            className="button button-primary ddoo__test-button"
            onClick={() => {
              wp.apiFetch({
                path: "/didgeridoo/v1/test-dns",
                method: "POST",
                data: {
                  didgeridoo_subdomain: domainName,
                },
              })
                .then((data) => {
                  setSuccessMessages({ success: data });
                })
                .catch((error) => {
                  setErrors(error);
                  setSuccessMessages({});
                });
            }}
          >
            Test Subdomain
          </button>
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
            ></button>
          </div>
        )}

        <div className="ddoo__row">
          <label>{__("cool-username.", "didgeridoo")}</label>
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

        <div className="ddoo__row">
          <button
            className="button button-primary"
            onClick={() => {
              wp.apiFetch({
                path: "/didgeridoo/v1/options",
                method: "POST",
                data: {
                  didgeridoo_main_did: mainDid,
                  didgeridoo_subdomain: domainName,
                  didgeridoo_enable_org_mode: enableOrgMode,
                },
              })
                .then((data) => {
                  setErrors({});
                  setSuccessMessages({ success: data });
                })
                .catch((error) => {
                  setErrors(error);
                  setSuccessMessages({});
                });
            }}
          >
            {__("Save", "didgeridoo")}
          </button>
        </div>
      </div>
    </div>
  );
};

document.addEventListener("DOMContentLoaded", () => {
  const rootElement = document.getElementById("didgeridoo-settings");
  if (rootElement) {
    const root = createRoot(rootElement);
    root.render(<App />);
  }
});
