import React from "react";
import { format } from "date-fns";
import DatePicker from "react-datepicker";
import "react-datepicker/dist/react-datepicker.css";

const CompactTimePicker = ({ value, onChange }) => {
  const h = value ? value.split(":")[0] : "";
  const m = value ? value.split(":")[1] : "";

  const handleHour = (e) => {
    let val = e.target.value.replace(/\D/g, "");
    if (val.length > 2) val = val.slice(-2);
    if (parseInt(val) > 23) val = "23";
    onChange(`${val}:${m || "00"}`);
  };

  const handleMin = (e) => {
    let val = e.target.value.replace(/\D/g, "");
    if (val.length > 2) val = val.slice(-2);
    if (parseInt(val) > 59) val = "59";
    onChange(`${h || "00"}:${val}`);
  };

  const handleBlur = () => {
    const cleanH = h ? h.padStart(2, "0") : "";
    const cleanM = m ? m.padStart(2, "0") : "";
    if (cleanH || cleanM) {
      onChange(`${cleanH || "00"}:${cleanM || "00"}`);
    }
  };

  return (
    <div style={{ display: "flex", gap: "8px", alignItems: "center" }}>
      <input
        type="text"
        placeholder="HH"
        value={h}
        onChange={handleHour}
        onBlur={handleBlur}
        style={{
          width: "60px",
          height: "48px",
          textAlign: "center",
          fontSize: "1.1rem",
          fontWeight: "700",
          border: "1px solid #d1d5db",
          borderRadius: "12px",
          padding: "0 8px",
        }}
      />
      <span style={{ fontSize: "1.4rem", fontWeight: "700", color: "#6b7280" }}>:</span>
      <input
        type="text"
        placeholder="MM"
        value={m}
        onChange={handleMin}
        onBlur={handleBlur}
        style={{
          width: "60px",
          height: "48px",
          textAlign: "center",
          fontSize: "1.1rem",
          fontWeight: "700",
          border: "1px solid #d1d5db",
          borderRadius: "12px",
          padding: "0 8px",
        }}
      />
    </div>
  );
};

const money = (n) => (n === null || n === undefined || n === "" ? "—" : `$${Number(n).toFixed(2)}`);

// Shows the old vs. new payment figures returned by api/update-roster-time's
// confirm_required response, whichever billing flow (client Stripe hold vs
// contractor invoice) the breakdown belongs to.
const PaymentBreakdownRow = ({ label, oldVal, newVal }) => {
  const changed = Number(oldVal ?? 0) !== Number(newVal ?? 0);
  return (
    <div style={{ display: "flex", justifyContent: "space-between", padding: "6px 0", borderBottom: "1px solid #f1f5f9" }}>
      <span style={{ color: "#6b7280", fontSize: "0.9rem" }}>{label}</span>
      <span style={{ fontSize: "0.9rem", fontWeight: 600 }}>
        {changed ? (
          <>
            <span style={{ color: "#9ca3af", textDecoration: "line-through", marginRight: 8 }}>{money(oldVal)}</span>
            <span style={{ color: "#0f766e" }}>{money(newVal)}</span>
          </>
        ) : (
          <span style={{ color: "#111827" }}>{money(newVal)}</span>
        )}
      </span>
    </div>
  );
};

export default function TimeEditModal({
  modal,
  closeModal,
  editForm,
  setEditForm,
  timeEditError,
  clearTimeEditError,
  handleSave,
  saveLoading,
  breakdown,
  onBack,
}) {
  const shift = modal?.shift;
  const site = modal?.site;

  if (breakdown) {
    const oldPay = breakdown.old_payment || {};
    const newPay = breakdown.new_payment || {};
    const isContractorFlow = breakdown.billing_flow === "contractor_invoice";

    return (
      <div
        onClick={closeModal}
        style={{
          position: "fixed", top: 0, left: 0, width: "100vw", height: "100vh",
          backgroundColor: "rgba(0,0,0,0.45)", zIndex: 99999,
          display: "flex", alignItems: "center", justifyContent: "center", padding: "16px",
        }}
      >
        <div
          onClick={(e) => e.stopPropagation()}
          style={{
            width: "100%", maxWidth: "560px", maxHeight: "92vh", backgroundColor: "#fff",
            borderRadius: "20px", overflow: "hidden", boxShadow: "0 22px 80px rgba(16,24,40,0.18)",
            display: "flex", flexDirection: "column",
          }}
        >
          <div style={{ padding: "24px 28px", borderBottom: "1px solid #eef2f7" }}>
            <h3 style={{ margin: 0, fontSize: "1.2rem", fontWeight: 700 }}>Confirm shift time change</h3>
            <p style={{ margin: "6px 0 0", color: "#6b7280", fontSize: "0.95rem", textTransform: "none" }}>
              Changing this shift's time changes what you'll be charged. Review the breakdown below before confirming.
            </p>
          </div>

          <div style={{ padding: "22px 28px", overflowY: "auto" }}>
            <div style={{ display: "flex", justifyContent: "space-between", marginBottom: "18px" }}>
              <div>
                <div style={{ fontSize: "0.85rem", color: "#6b7280" }}>Current time</div>
                <div style={{ fontSize: "0.95rem", fontWeight: 700 }}>
                  {breakdown.old_start} – {breakdown.old_end}
                </div>
              </div>
              <div style={{ textAlign: "right" }}>
                <div style={{ fontSize: "0.85rem", color: "#6b7280" }}>New time</div>
                <div style={{ fontSize: "0.95rem", fontWeight: 700, color: "#0f766e" }}>
                  {breakdown.new_start} – {breakdown.new_end}
                </div>
              </div>
            </div>

            <div style={{ background: "#f8fafc", borderRadius: "14px", padding: "16px 18px", border: "1px solid #e2e8f0" }}>
              <div style={{ fontWeight: 700, fontSize: "0.92rem", marginBottom: "8px", color: "#111827" }}>
                Payment breakdown
              </div>

              {isContractorFlow ? (
                <>
                  <PaymentBreakdownRow label="Subtotal" oldVal={oldPay.gross_subtotal} newVal={newPay.gross_subtotal} />
                  <PaymentBreakdownRow label="Discount" oldVal={oldPay.discount} newVal={newPay.discount} />
                  <PaymentBreakdownRow label="Net (ex. GST)" oldVal={oldPay.net_taxable} newVal={newPay.net_taxable} />
                  <PaymentBreakdownRow label="GST" oldVal={oldPay.gst_amount} newVal={newPay.gst_amount} />
                  <PaymentBreakdownRow label="Total payable" oldVal={oldPay.total_payable} newVal={newPay.total_payable} />
                </>
              ) : newPay.grand_total !== undefined ? (
                <>
                  <PaymentBreakdownRow label="Subtotal" oldVal={oldPay.base_total} newVal={newPay.base_total} />
                  <PaymentBreakdownRow label="Discount" oldVal={oldPay.discount} newVal={newPay.discount} />
                  <PaymentBreakdownRow label="GST" oldVal={oldPay.service_fee} newVal={newPay.service_fee} />
                  <PaymentBreakdownRow label="Grand total" oldVal={oldPay.grand_total} newVal={newPay.grand_total} />
                  <PaymentBreakdownRow label="Amount charged" oldVal={oldPay.amount_charged} newVal={newPay.amount_charged} />
                  <PaymentBreakdownRow label="Balance due" oldVal={oldPay.balance} newVal={newPay.balance} />
                </>
              ) : (
                <PaymentBreakdownRow label="Job amount" oldVal={breakdown.old_job_amount} newVal={breakdown.new_job_amount} />
              )}
            </div>

            {timeEditError && (
              <div style={{ color: "#dc2626", fontSize: "0.9rem", fontWeight: 500, marginTop: "14px" }}>
                {timeEditError}
              </div>
            )}
          </div>

          <div style={{ display: "flex", justifyContent: "flex-end", gap: "12px", padding: "18px 28px", borderTop: "1px solid #eef2f7" }}>
            <button
              onClick={onBack}
              type="button"
              style={{
                padding: "11px 22px", borderRadius: "12px", border: "1px solid #d1d5db",
                background: "#fff", color: "#111827", cursor: "pointer", fontSize: "0.95rem",
              }}
            >
              Back
            </button>
            <button
              onClick={handleSave}
              type="button"
              disabled={saveLoading}
              style={{
                padding: "11px 22px", borderRadius: "12px", border: "none",
                background: saveLoading ? "#6ee7b7" : "#0f766e", color: "#fff",
                cursor: "pointer", fontSize: "0.95rem", opacity: saveLoading ? 0.75 : 1,
              }}
            >
              {saveLoading ? "Updating..." : "Confirm & Update"}
            </button>
          </div>
        </div>
      </div>
    );
  }

  return (
    <div
      onClick={closeModal}
      style={{
        position: "fixed",
        top: 0,
        left: 0,
        width: "100vw",
        height: "100vh",
        backgroundColor: "rgba(0,0,0,0.45)",
        zIndex: 99999,
        display: "flex",
        alignItems: "center",
        justifyContent: "center",
        padding: "16px",
      }}
    >
      <div
        onClick={(e) => e.stopPropagation()}
        style={{
          width: "100%",
          maxWidth: "780px",
          maxHeight: "92vh",
          backgroundColor: "#fff",
          borderRadius: "20px",
          overflow: "hidden",
          boxShadow: "0 22px 80px rgba(16,24,40,0.18)",
          display: "flex",
          flexDirection: "column",
        }}
      >
        <div style={{ padding: "24px 28px", borderBottom: "1px solid #eef2f7", display: "flex", justifyContent: "space-between", alignItems: "center" }}>
          <div>
            <h3 style={{ margin: 0, fontSize: "1.2rem", fontWeight: 700 }}>Edit Job Times & Date</h3>
            <p style={{ margin: "6px 0 0", color: "#6b7280", fontSize: "0.95rem", textTransform: "none" }}>
              Update the date and times for this roster entry. Site and staff assignment stay unchanged.
            </p>
          </div>
          <button
            onClick={closeModal}
            style={{
              width: "36px",
              height: "36px",
              borderRadius: "50%",
              border: "1px solid #fff",
              background: "#0A7C6E",
              color: "#111827",
              fontSize: "18px",
              lineHeight: 1,
              cursor: "pointer",
            }}
          >
            <i className="fa fa-times" style={{ color: "#fff" }}></i>
          </button>
        </div>

        <div style={{ display: "grid", gridTemplateColumns: "1fr 1fr", gap: "24px", padding: "24px 28px", overflowY: "auto" }}>

          {/* Box 1 Container */}
          <div style={{ minWidth: 0, height: "100%" }}>
            <div style={{ background: "#f8fafc", borderRadius: "18px", padding: "22px", border: "1px solid #e2e8f0", height: "100%", boxSizing: "border-box" }}>
              <div style={{ marginBottom: "20px" }}>
                <div style={{ fontSize: "1rem", fontWeight: 600, color: "#6b7280" }}>Site</div>
                <div style={{ fontSize: "1rem", fontWeight: 700, marginTop: "6px", color: "#111827" }}>
                  {site?.displayName || "Roster site"}
                </div>
                {site?.address && (
                  <div style={{ marginTop: "8px", color: "#4b5563", fontSize: "0.92rem" }}>
                    {site.address || site.site_address}
                  </div>
                )}
              </div>

              <div style={{ display: "grid", gap: "16px" }}>
                <div>
                  <div style={{ fontSize: "1rem", fontWeight: 600, color: "#6b7280", marginBottom: "6px" }}>Current schedule</div>
                  <div style={{ fontSize: "1.05rem", fontWeight: 700, color: "#111827" }}>{modal?.dateStr || "No date"}</div>
                  <div style={{ fontSize: "1rem", fontWeight: 700, color: "#0f766e", marginTop: "4px" }}>
                    {shift?.startDate && shift?.endDate ? `${format(shift.startDate, "HH:mm")} – ${format(shift.endDate, "HH:mm")}` : "Not available"}
                  </div>
                </div>
              </div>
            </div>
          </div>

          {/* Box 2 Container */}
          <div style={{ minWidth: 0, height: "100%" }}>
            <div style={{ background: "#ffffff", borderRadius: "18px", padding: "22px", border: "1px solid #e5e8ef", height: "100%", boxSizing: "border-box" }}>
              <div style={{ marginBottom: "16px", fontSize: "0.95rem", fontWeight: 900, color: "#111827" }}>Edit schedule</div>
              <div style={{ display: "grid", gap: "16px" }}>
                <div>
                  <label style={{ fontSize: "0.9rem", fontWeight: 600, color: "#374151", marginBottom: "8px", display: "flex", alignItems: "center", gap: "6px" }}>
                    <i className="fa-regular fa-calendar-days" style={{ color: "#0A7C6E" }}></i> Shift Date
                  </label>
                  <DatePicker
                    selected={editForm.date || (shift?.startDate ? new Date(shift.startDate) : new Date())}
                    onChange={(d) => {
                      clearTimeEditError?.();
                      setEditForm((prev) => ({ ...prev, date: d }));
                    }}
                    dateFormat="dd/MM/yyyy"
                    className="form-control"
                    placeholderText="dd/mm/yyyy"
                    customInput={
                      <input
                        style={{
                          width: "100%",
                          height: "44px",
                          border: "1px solid #d1d5db",
                          borderRadius: "12px",
                          padding: "0 14px",
                          fontSize: "0.95rem",
                          fontWeight: "600",
                          color: "#111827",
                          backgroundColor: "#fff",
                          cursor: "pointer",
                        }}
                      />
                    }
                  />
                </div>
                <div>
                  <label style={{ fontSize: "0.9rem", fontWeight: 600, color: "#374151", marginBottom: "8px", display: "flex", alignItems: "center", gap: "6px" }}>
                    <i className="fa-regular fa-clock" style={{ color: "#0A7C6E" }}></i> Start time (24-Hour Format)
                  </label>
                  <CompactTimePicker
                    value={editForm.startTime}
                    onChange={(val) => {
                      clearTimeEditError?.();
                      setEditForm((prev) => ({ ...prev, startTime: val }));
                    }}
                  />
                </div>
                <div>
                  <label style={{ fontSize: "0.9rem", fontWeight: 600, color: "#374151", marginBottom: "8px", display: "flex", alignItems: "center", gap: "6px" }}>
                    <i className="fa-regular fa-clock" style={{ color: "#0A7C6E" }}></i> End time (24-Hour Format)
                  </label>
                  <CompactTimePicker
                    value={editForm.endTime}
                    onChange={(val) => {
                      clearTimeEditError?.();
                      setEditForm((prev) => ({ ...prev, endTime: val }));
                    }}
                  />
                </div>
              </div>
            </div>
          </div>

        </div>

        <div style={{ display: "flex", justifyContent: "space-between", alignItems: "center", padding: "18px 28px", borderTop: "1px solid #eef2f7" }}>
          {timeEditError ? (
            <div style={{ color: "#dc2626", fontSize: "0.95rem", fontWeight: 500 }}>
              {timeEditError}
            </div>
          ) : (
            <div style={{ color: "#6b7280", fontSize: "0.92rem", textTransform: "none" }}>Date and time values may be changed here.</div>
          )}
          <div style={{ display: "flex", gap: "12px" }}>
            <button
              onClick={closeModal}
              type="button"
              style={{
                padding: "11px 22px",
                borderRadius: "12px",
                border: "1px solid #d1d5db",
                background: "#fff",
                color: "#111827",
                cursor: "pointer",
                fontSize: "0.95rem",
              }}
            >
              Cancel
            </button>
            <button
              onClick={handleSave}
              type="button"
              disabled={saveLoading}
              style={{
                padding: "11px 22px",
                borderRadius: "12px",
                border: "none",
                background: saveLoading ? "#6ee7b7" : "#0f766e",
                color: "#fff",
                cursor: "pointer",
                fontSize: "0.95rem",
                opacity: saveLoading ? 0.75 : 1,
              }}
            >
              {saveLoading ? "Saving..." : "Save Changes"}
            </button>
          </div>
        </div>
      </div>
    </div>
  );
}