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

import React from "react";

const useState = wp.element.useState;

const PopupModal = ({ title, children }) => {
  const [isOpen, setIsOpen] = useState(false);

  return (
    <div className="modal">
      <button className="modal__open-button" onClick={() => setIsOpen(true)}>
        ?
      </button>
      <div className={`modal__box ${isOpen ? "modal__box--open" : ""}`}>
        <div
          className="modal__background"
          onClick={() => setIsOpen(false)}
        ></div>
        <div className="modal__content">
          <div className="modal__header">
            <h2>{title}</h2>
            <span
              className="modal__close-button"
              onClick={() => setIsOpen(false)}
            >
              &times;
            </span>
          </div>
          <div className="modal__body">{children}</div>
        </div>
      </div>
    </div>
  );
};

export default PopupModal;
