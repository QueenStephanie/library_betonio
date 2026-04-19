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

  function escapeHtml(value) {
    return asString(value, '').replace(/[&<>"']/g, function (char) {
      if (char === '&') return '&amp;';
      if (char === '<') return '&lt;';
      if (char === '>') return '&gt;';
      if (char === '"') return '&quot;';
      return '&#39;';
    });
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

  function isMobileLikeDevice() {
    if (typeof window.matchMedia === 'function' && window.matchMedia('(pointer: coarse)').matches) {
      return true;
    }

    var ua = (window.navigator && window.navigator.userAgent) ? window.navigator.userAgent : '';
    return /Android|iPhone|iPad|iPod|Mobile|Opera Mini|IEMobile/i.test(ua);
  }

  function downloadTextFile(filename, content) {
    var blob = new Blob([content], { type: 'text/html;charset=utf-8' });
    var url = URL.createObjectURL(blob);
    var anchor = document.createElement('a');
    anchor.href = url;
    anchor.download = filename;
    anchor.style.display = 'none';
    document.body.appendChild(anchor);
    anchor.click();
    document.body.removeChild(anchor);
    URL.revokeObjectURL(url);
  }

  function safeFileName(filename) {
    var base = typeof filename === 'string' && filename.length > 0 ? filename : 'receipt.html';
    return base.replace(/[^a-zA-Z0-9._-]/g, '_');
  }

  function handleReceiptAction(alert) {
    var receipt = (alert && typeof alert === 'object' && alert.receipt && typeof alert.receipt === 'object')
      ? alert.receipt
      : null;

    if (!receipt) {
      return Promise.resolve();
    }

    if (isMobileLikeDevice()) {
      var mobileDownloadUrl = typeof receipt.downloadUrl === 'string' && receipt.downloadUrl.length > 0
        ? receipt.downloadUrl
        : (typeof receipt.viewUrl === 'string' ? receipt.viewUrl : '');

      if (!mobileDownloadUrl) {
        return Promise.resolve();
      }

      return fetch(mobileDownloadUrl, { credentials: 'same-origin' })
        .then(function (response) {
          if (!response.ok) {
            throw new Error('Receipt download failed');
          }

          return response.text();
        })
        .then(function (html) {
          downloadTextFile(safeFileName(receipt.mobileFileName), html);
        })
        .catch(function () {
          openWindow(mobileDownloadUrl);
        });
    }

    var printUrl = typeof receipt.printUrl === 'string' && receipt.printUrl.length > 0 ? receipt.printUrl : '';
    if (printUrl) {
      openWindow(printUrl);
    }

    return Promise.resolve();
  }

  function runReceiptOverlay(alert) {
    return new Promise(function (resolve) {
      if (typeof window.Swal !== 'object' || window.Swal === null || typeof window.Swal.fire !== 'function') {
        Promise.resolve(handleReceiptAction(alert)).finally(function () {
          redirectTo(alert.redirect);
          redirectTo(alert.onConfirmRedirect);
          resolve();
        });
        return;
      }

      var isSuccess = asString(alert.type, 'success') === 'success';
      var isMobile = isMobileLikeDevice();
      var receipt = alert.receipt || {};
      var receiptCode = asString(receipt.code, 'N/A');
      var actionText = isMobile ? 'Download receipt file' : 'Open print view (Ctrl+P)';

      window.Swal.fire({
        icon: isSuccess ? 'success' : 'info',
        title: asString(alert.title, 'Transaction Receipt'),
        html:
          '<p style="margin:0 0 10px;">' + escapeHtml(asString(alert.message, '')) + '</p>' +
          '<div style="padding:10px 12px;border:1px solid #f0d8c8;background:#fff7f1;border-radius:10px;text-align:left;">' +
          '<div><strong>Receipt:</strong> ' + escapeHtml(receiptCode) + '</div>' +
          '<div><strong>Action:</strong> ' + escapeHtml(actionText) + '</div>' +
          '</div>',
        confirmButtonText: isMobile ? 'Download' : 'Print',
        confirmButtonColor: '#d24718',
        showCancelButton: true,
        cancelButtonText: 'Close',
        cancelButtonColor: '#999',
        allowOutsideClick: false,
        allowEscapeKey: false
      }).then(function (result) {
        if (result.isConfirmed) {
          Promise.resolve(handleReceiptAction(alert)).finally(function () {
            redirectTo(alert.redirect);
            redirectTo(alert.onConfirmRedirect);
            resolve();
          });
          return;
        }

        redirectTo(alert.redirect);
        redirectTo(alert.onCancelRedirect);
        resolve();
      });
    });
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
            Promise.resolve(handleReceiptAction(alert)).finally(function () {
              redirectTo(alert.redirect);
              redirectTo(alert.onConfirmRedirect);
              if (!alert.receipt) {
                openWindow(alert.onConfirmOpen);
              }
              resolve(true);
            });
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
            Promise.resolve(handleReceiptAction(alert)).finally(function () {
              redirectTo(alert.redirect);
              if (!alert.receipt) {
                openWindow(alert.onConfirmOpen);
              }
              resolve(true);
            });
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
        Promise.resolve(handleReceiptAction(alert)).finally(function () {
          redirectTo(alert.redirect);
          redirectTo(alert.onConfirmRedirect);
          if (!alert.receipt) {
            openWindow(alert.onConfirmOpen);
          }
          resolve();
        });
      });
    });
  }

  function runSingleAlert(alert) {
    if (alert && alert.receipt) {
      return runReceiptOverlay(alert);
    }

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
