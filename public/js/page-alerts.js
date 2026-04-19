/**
 * Page Alerts Runner
 * Executes server-provided alert descriptors with optional redirects.
 */
(function () {
  'use strict';

  if (window.PageAlerts) {
    return;
  }

  function asString(value, fallback) {
    return typeof value === 'string' && value.length > 0 ? value : fallback;
  }

  function redirectTo(path) {
    if (typeof path === 'string' && path.length > 0) {
      window.location.href = path;
    }
  }

  function openWindow(path) {
    if (typeof path !== 'string' || path.length === 0) {
      return;
    }

    window.open(path, '_blank', 'noopener');
  }

  function runWithSweetAlerts(alert) {
    return new Promise(function (resolve) {
      if (typeof window.SweetAlerts !== 'object' || window.SweetAlerts === null) {
        resolve(false);
        return;
      }

      if (typeof alert.method === 'string' && typeof window.SweetAlerts[alert.method] === 'function') {
        window.SweetAlerts[alert.method](function () {
          redirectTo(alert.redirect);
          resolve(true);
        });
        return;
      }

      var type = asString(alert.type, 'info');

      if (type === 'warning') {
        window.SweetAlerts.warning(
          asString(alert.title, 'Warning'),
          asString(alert.message, ''),
          asString(alert.confirmText, 'Yes'),
          asString(alert.cancelText, 'Cancel'),
          function () {
            redirectTo(alert.redirect);
            redirectTo(alert.onConfirmRedirect);
            openWindow(alert.onConfirmOpen);
            resolve(true);
          },
          function () {
            redirectTo(alert.onCancelRedirect);
            resolve(true);
          }
        );
        return;
      }

      if (typeof window.SweetAlerts[type] === 'function') {
        window.SweetAlerts[type](
          asString(alert.title, 'Notice'),
          asString(alert.message, ''),
          function () {
            redirectTo(alert.redirect);
            openWindow(alert.onConfirmOpen);
            resolve(true);
          }
        );
        return;
      }

      resolve(false);
    });
  }

  function runWithSwalFallback(alert) {
    return new Promise(function (resolve) {
      if (typeof window.Swal !== 'object' || window.Swal === null || typeof window.Swal.fire !== 'function') {
        redirectTo(alert.redirect);
        resolve();
        return;
      }

      var type = asString(alert.type, 'info');
      window.Swal.fire({
        icon: type,
        title: asString(alert.title, 'Notice'),
        text: asString(alert.message, ''),
        confirmButtonText: asString(alert.confirmText, 'OK')
      }).then(function () {
        redirectTo(alert.redirect);
        redirectTo(alert.onConfirmRedirect);
        openWindow(alert.onConfirmOpen);
        resolve();
      });
    });
  }

  function runSingleAlert(alert) {
    return runWithSweetAlerts(alert).then(function (handled) {
      if (handled) {
        return;
      }

      return runWithSwalFallback(alert);
    });
  }

  function run(alerts) {
    var queue = Array.isArray(alerts) ? alerts : [];
    var chain = Promise.resolve();

    queue.forEach(function (alert) {
      chain = chain.then(function () {
        return runSingleAlert(alert || {});
      });
    });

    return chain;
  }

  window.PageAlerts = {
    run: run
  };
})();
