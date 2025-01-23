import React from "react";

const useState = wp.element.useState;

const PopupModal = ({ title, children }) => {
  const [isOpen, setIsOpen] = useState(false);

  return (
    <div className="modal">
      <button className="modal__open-button" onClick={() => setIsOpen(true)}>?</button>
      <div className={`modal__box ${isOpen ? "modal__box--open" : ""}`}>
        <div className="modal__background" onClick={() => setIsOpen(false)}></div>
        <div className="modal__content">
          <div className="modal__header">
            <h2>{title}</h2>
            <span className="modal__close-button" onClick={() => setIsOpen(false)}>&times;</span>
          </div>
          <div className="modal__body">{children}</div>
        </div>
      </div>
    </div>
  );
};

export default PopupModal;
