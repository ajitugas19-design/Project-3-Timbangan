/**
 * EnterToNext - Utility to move focus to next input on Enter key press
 * Usage: initEnterNext(formElementOrSelector)
 */
(function () {
  "use strict";

  function isVisible(el) {
    if (!el) return false;
    const style = window.getComputedStyle(el);
    return style.display !== "none" && style.visibility !== "hidden";
  }

  function getFocusableElements(form) {
    // Select all naturally focusable form elements
    const selectors = [
      'input:not([type="hidden"])',
      "select",
      "textarea",
      'button:not([type="submit"])',
    ];
    return Array.from(form.querySelectorAll(selectors.join(","))).filter(
      function (el) {
        // Skip disabled, readonly, hidden, or invisible elements
        if (el.disabled) return false;
        if (el.readOnly && el.type !== "checkbox" && el.type !== "radio")
          return false;
        if (!isVisible(el)) return false;
        // Skip hidden inputs (double check)
        if (el.type === "hidden") return false;
        return true;
      },
    );
  }

  function handleEnterKey(e) {
    if (e.key !== "Enter") return;

    const current = e.target;
    const form = current.closest("form");
    if (!form) return;

    // Allow Enter on textarea (multi-line) unless Ctrl/Shift is pressed
    if (current.tagName === "TEXTAREA" && !(e.ctrlKey || e.shiftKey)) {
      // For textarea, let default behavior (newline) unless user wants to move
      // If you want Enter to move focus from textarea too, remove this block
      return;
    }

    e.preventDefault();

    const focusables = getFocusableElements(form);
    const idx = focusables.indexOf(current);
    if (idx === -1) return;

    const next = focusables[idx + 1];
    if (next) {
      next.focus();
      // If it's a text input, select all text for easier editing
      if (
        next.tagName === "INPUT" &&
        [
          "text",
          "password",
          "number",
          "email",
          "tel",
          "url",
          "search",
        ].includes(next.type)
      ) {
        next.select();
      }
    } else {
      // Last element: focus submit button if exists
      const submitBtn = form.querySelector(
        'button[type="submit"], input[type="submit"]',
      );
      if (submitBtn && isVisible(submitBtn)) {
        submitBtn.focus();
      }
    }
  }

  window.initEnterNext = function (formOrSelector) {
    let form;
    if (typeof formOrSelector === "string") {
      form = document.querySelector(formOrSelector);
    } else {
      form = formOrSelector;
    }
    if (!form) return;

    form.addEventListener("keydown", handleEnterKey);
  };

  // Auto-init on forms with data-enter-next attribute
  document.addEventListener("DOMContentLoaded", function () {
    document
      .querySelectorAll('form[data-enter-next="true"]')
      .forEach(function (form) {
        initEnterNext(form);
      });
  });
})();
