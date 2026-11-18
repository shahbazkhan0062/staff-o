import React, { useState, useMemo, useRef } from "react";
import { useSelector } from "react-redux";
import { toast } from "react-toastify";
import Loader from "../components/Loader";
import SignaturePad from "../components/contracts/SignaturePad";
import useFetch from "../hooks/useFetch";
import useSubmit from "../hooks/useSubmit";
import { apiURL } from "../utils/exports";
import "./EmploymentAgreements.css";

/**
 * Admin screen: lists every active Staffoo staff member with a one-click
 * "Send Agreement" button per row. Everything (Schedule 1 details,
 * employment category, classification) is auto-filled server-side —
 * Category defaults to Casual — and the EMPLOYEE confirms/changes category
 * + classification themselves on the signing page right before they sign.
 * There is nothing for HR to fill in here; sending itself is unchanged from
 * before, just triggered per-row instead of via a dropdown.
 *
 * Below that, the existing Employment Agreements table still lets Ops
 * countersign once the employee has signed, and shows every agreement with
 * a link to the current PDF — unchanged.
 *
 * Uses the app's own useFetch/useSubmit hooks (isAuth: true) so the admin's
 * real auth token — held in Redux (state.auth.token) — goes out on every
 * call, the same way every other admin screen in this app authenticates.
 *
 * Drop this in as a route (e.g. /employment-agreements).
 */

const EMPLOYMENT_TYPES = [
  { value: "full_time", label: "Full-Time" },
  { value: "part_time", label: "Part-Time" },
  { value: "casual", label: "Casual" },
];

// Backend sends a raw ISO timestamp for commencement_date (straight off a
// Carbon date column) — normalize it into one human-readable date instead
// of leaking "2026-11-17T13:00:00.000000Z" onto the screen.
const formatDisplayDate = (value) => {
  if (!value) return "—";
  const d = new Date(value);
  if (Number.isNaN(d.getTime())) return value;
  return d.toLocaleDateString("en-AU", { day: "numeric", month: "short", year: "numeric" });
};

export default function EmploymentAgreements() {
  // The logged-in admin's own id — sent along as admin_id so the backend's
  // created_by / employer_signed_by columns are reliably populated even on
  // routes where auth()->id() doesn't resolve (same fallback pattern this
  // app already uses elsewhere, e.g. JobRosterController).
  const adminId = useSelector((state) => state.auth.userdata?.data?.id || state.auth.userdata?.id);

  const [statusFilter, setStatusFilter] = useState("");

  // Two separate screens ("Active Staff" to send agreements, "All Employment
  // Agreements" to track/countersign them) were previously stacked on one
  // page — split into tabs so only one shows at a time.
  const [activeMainTab, setActiveMainTab] = useState("staff");

  // Active Staff list controls — search + status filter + client-side
  // pagination, so the list stays usable with dozens/hundreds of staff
  // instead of one long unfiltered table.
  const [staffSearch, setStaffSearch] = useState("");
  const [staffStatusFilter, setStaffStatusFilter] = useState("");
  const [staffPage, setStaffPage] = useState(1);
  const STAFF_PAGE_SIZE = 10;

  const listEndpoint = useMemo(() => {
    const qs = statusFilter ? `?status=${statusFilter}` : "";
    return `api/admin/employment-agreements${qs}`;
  }, [statusFilter]);

  const { data: listResponse, loading: loadingList, refetch: refetchList } = useFetch(listEndpoint, { isAuth: true });
  const agreements = listResponse?.data?.data || listResponse?.data || [];

  // Every active Staffoo staff member — pulled from the same list used
  // elsewhere in admin — shown as a table with its own "Send Agreement"
  // button per row instead of a dropdown.
  const { data: staffResponse, loading: staffListLoading } = useFetch("api/admin/get-staffoo-staff?per_page=1000", { isAuth: true });
  const staffList = staffResponse?.data?.data || staffResponse?.data || [];

  // Latest agreement per staff user_id, so each staff row can show whether
  // one has already been sent (and its status) instead of just a blind
  // "Send" button every time.
  const latestAgreementByUserId = useMemo(() => {
    const map = {};
    for (const a of agreements) {
      const uid = a.user_id ?? a.user?.id;
      if (!uid) continue;
      const existing = map[uid];
      if (!existing || new Date(a.created_at) > new Date(existing.created_at)) {
        map[uid] = a;
      }
    }
    return map;
  }, [agreements]);

  const filteredStaff = useMemo(() => {
    const term = staffSearch.trim().toLowerCase();
    return staffList.filter((s) => {
      if (term) {
        const haystack = `${s.name || ""} ${s.email || ""}`.toLowerCase();
        if (!haystack.includes(term)) return false;
      }
      if (staffStatusFilter) {
        const existing = latestAgreementByUserId[s.id];
        if (staffStatusFilter === "not_sent") {
          if (existing) return false;
        } else if (!existing || existing.status !== staffStatusFilter) {
          return false;
        }
      }
      return true;
    });
  }, [staffList, staffSearch, staffStatusFilter, latestAgreementByUserId]);

  const staffTotalPages = Math.max(1, Math.ceil(filteredStaff.length / STAFF_PAGE_SIZE));
  const staffPageClamped = Math.min(staffPage, staffTotalPages);
  const pagedStaff = filteredStaff.slice(
    (staffPageClamped - 1) * STAFF_PAGE_SIZE,
    staffPageClamped * STAFF_PAGE_SIZE
  );

  // Actions — separate useSubmit instances so each tracks its own loading
  // flag without the Send/Resend/Countersign buttons fighting over one.
  const { submit: submitSend, loading: sending } = useSubmit({ isAuth: true });
  const [sendingUserId, setSendingUserId] = useState(null);
  const { submit: submitResend } = useSubmit({ isAuth: true });
  const { submit: submitCounterSignReq, loading: counterSigning } = useSubmit({ isAuth: true });

  // Countersign modal
  const [counterSignTarget, setCounterSignTarget] = useState(null); // agreement object
  const [employerSignatureName, setEmployerSignatureName] = useState("");
  const [counterSigMode, setCounterSigMode] = useState("draw"); // "draw" | "auto" — same as the employee signing page
  const sigPadRef = useRef(null);

  /* ---------------- Send-agreement (one click, nothing to fill in) ---------------- */

  const sendAgreement = async (userId) => {
    if (!userId) return;
    setSendingUserId(userId);
    const json = await submitSend(`api/admin/staff/${userId}/employment-agreement/send`, { admin_id: adminId });
    setSendingUserId(null);
    if (!json?.success) {
      // useSubmit already toasts the error for non-2xx / success:false responses.
      return;
    }
    toast.success(json?.message || "Employment agreement emailed to the employee for signature.");
    refetchList();
  };

  /* ---------------- Countersign flow ---------------- */

  const openCounterSign = (agreement) => {
    setCounterSignTarget(agreement);
    setEmployerSignatureName("");
    setCounterSigMode("draw");
  };

  const submitCounterSign = async () => {
    if (!counterSignTarget) return;
    const sigData = sigPadRef.current?.toDataURL();
    if (!employerSignatureName.trim() || !sigData || sigPadRef.current?.isEmpty()) {
      toast.error("Enter the Operations Manager's name and signature.");
      return;
    }
    const json = await submitCounterSignReq(`api/admin/employment-agreement/${counterSignTarget.id}/countersign`, {
      signature_name: employerSignatureName.trim(),
      signature_image: sigData,
      admin_id: adminId,
    });
    if (!json?.success) return;
    toast.success(json?.message || "Agreement fully executed and emailed with statutory attachments.");
    setCounterSignTarget(null);
    refetchList();
  };

  const resend = async (id) => {
    const json = await submitResend(`api/admin/employment-agreement/${id}/resend`, null);
    if (json?.success) toast.success(json?.message || "Done.");
  };

  const statusBadge = (status) => {
    const map = {
      pending_signature: { label: "Pending Employee Signature", cls: "ea-badge-pending" },
      employee_signed: { label: "Awaiting Countersignature", cls: "ea-badge-partial" },
      fully_executed: { label: "Fully Executed", cls: "ea-badge-done" },
      cancelled: { label: "Cancelled", cls: "ea-badge-cancelled" },
    };
    const info = map[status] || { label: status, cls: "ea-badge-pending" };
    return <span className={`ea-badge ${info.cls}`}>{info.label}</span>;
  };

  return (
    <div className="ea-page">
      <div className="ea-header-row">
        <h1>Employment Agreements</h1>
      </div>

      <div className="ea-tabs">
        <button
          type="button"
          className={`ea-tab-btn ${activeMainTab === "staff" ? "ea-tab-btn-active" : ""}`}
          onClick={() => setActiveMainTab("staff")}
        >
          Active Staff
        </button>
        <button
          type="button"
          className={`ea-tab-btn ${activeMainTab === "agreements" ? "ea-tab-btn-active" : ""}`}
          onClick={() => setActiveMainTab("agreements")}
        >
          All Employment Agreements
        </button>
      </div>

      {activeMainTab === "staff" && (
      <>
      {/* ---- Active Staff: one "Send Agreement" button per staff member ---- */}
      <div className="ea-header-row">
        <h2 className="ea-subheading">Active Staff</h2>
        <div className="ea-filter">
          <input
            type="text"
            className="ea-search-input"
            placeholder="Search by name or email..."
            value={staffSearch}
            onChange={(e) => { setStaffSearch(e.target.value); setStaffPage(1); }}
          />
          <select
            value={staffStatusFilter}
            onChange={(e) => { setStaffStatusFilter(e.target.value); setStaffPage(1); }}
          >
            <option value="">All staff</option>
            <option value="not_sent">Not Sent</option>
            <option value="pending_signature">Pending Employee Signature</option>
            <option value="employee_signed">Awaiting Countersignature</option>
            <option value="fully_executed">Fully Executed</option>
          </select>
        </div>
      </div>
      <p className="ea-modal-note" style={{ marginBottom: 16 }}>
        Sending is one click — every field (name, address, licence, etc.) is pulled automatically from the
        employee's registration data. Category of Employment defaults to <strong>Casual</strong>; the employee
        confirms or changes it (and their Classification Level) themselves on the signing page before they sign.
      </p>

      {staffListLoading ? (
        <Loader message="Loading staff..." />
      ) : (
        <>
          <table className="ea-table" style={{ marginBottom: 10 }}>
            <thead>
              <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Agreement Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              {pagedStaff.length === 0 && (
                <tr><td colSpan={4} className="ea-empty">No staff match this search/filter.</td></tr>
              )}
              {pagedStaff.map((s) => {
                const existing = latestAgreementByUserId[s.id];
                const isSendingThisRow = sending && sendingUserId === s.id;
                return (
                  <tr key={s.id}>
                    <td>{s.name}</td>
                    <td>{s.email}</td>
                    <td>{existing ? statusBadge(existing.status) : <span className="ea-badge ea-badge-cancelled">Not Sent</span>}</td>
                    <td className="ea-actions">
                      <button
                        className="ea-btn ea-btn-outline"
                        style={{ padding: "6px 14px", fontSize: 12.5 }}
                        onClick={() => sendAgreement(s.id)}
                        disabled={isSendingThisRow}
                      >
                        {isSendingThisRow ? "Sending..." : existing ? "Resend / Send Again" : "Send Agreement"}
                      </button>
                      {existing && (existing.signed_pdf_path || existing.pdf_path) && (
                        <a
                          href={`${apiURL}storage/${existing.signed_pdf_path || existing.pdf_path}`}
                          target="_blank"
                          rel="noreferrer"
                          className="ea-link"
                        >
                          View PDF
                        </a>
                      )}
                    </td>
                  </tr>
                );
              })}
            </tbody>
          </table>

          <div className="ea-pagination">
            <span className="ea-pagination-info">
              Showing {filteredStaff.length === 0 ? 0 : (staffPageClamped - 1) * STAFF_PAGE_SIZE + 1}
              –{Math.min(staffPageClamped * STAFF_PAGE_SIZE, filteredStaff.length)} of {filteredStaff.length} staff
            </span>
            <div className="ea-pagination-btns">
              <button
                className="ea-btn ea-btn-outline"
                style={{ padding: "6px 14px", fontSize: 12.5 }}
                onClick={() => setStaffPage((p) => Math.max(1, p - 1))}
                disabled={staffPageClamped <= 1}
              >
                Previous
              </button>
              <span className="ea-pagination-page">Page {staffPageClamped} of {staffTotalPages}</span>
              <button
                className="ea-btn ea-btn-outline"
                style={{ padding: "6px 14px", fontSize: 12.5 }}
                onClick={() => setStaffPage((p) => Math.min(staffTotalPages, p + 1))}
                disabled={staffPageClamped >= staffTotalPages}
              >
                Next
              </button>
            </div>
          </div>
        </>
      )}
      </>
      )}

      {activeMainTab === "agreements" && (
      <>
      {/* ---- All Employment Agreements (status tracking, resend, countersign) ---- */}
      <div className="ea-header-row">
        <h2 className="ea-subheading">All Employment Agreements</h2>
        <div className="ea-filter">
          <select value={statusFilter} onChange={(e) => setStatusFilter(e.target.value)}>
            <option value="">All statuses</option>
            <option value="pending_signature">Pending Employee Signature</option>
            <option value="employee_signed">Awaiting Countersignature</option>
            <option value="fully_executed">Fully Executed</option>
          </select>
        </div>
      </div>

      {loadingList ? (
        <Loader message="Loading agreements..." />
      ) : (
        <table className="ea-table">
          <thead>
            <tr>
              <th>Agreement #</th>
              <th>Employee</th>
              <th>Category</th>
              <th>Classification</th>
              <th>Commencement</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            {agreements.length === 0 && (
              <tr><td colSpan={7} className="ea-empty">No employment agreements yet.</td></tr>
            )}
            {agreements.map((a) => (
              <tr key={a.id}>
                <td>{a.agreement_number}</td>
                <td>{a.employee_name}</td>
                <td>{EMPLOYMENT_TYPES.find((t) => t.value === a.employment_type)?.label || a.employment_type}</td>
                <td>Level {a.classification_level}</td>
                <td>{formatDisplayDate(a.commencement_date)}</td>
                <td>{statusBadge(a.status)}</td>
                <td className="ea-actions">
                  {a.signed_pdf_path || a.pdf_path ? (
                    <a href={`${apiURL}storage/${a.signed_pdf_path || a.pdf_path}`} target="_blank" rel="noreferrer" className="ea-link">View PDF</a>
                  ) : null}
                  {a.fwis_attached && (
                    <a href={`${apiURL}storage/statutory/fwis.pdf`} target="_blank" rel="noreferrer" className="ea-link">FWIS</a>
                  )}
                  {a.ceis_attached && (
                    <a href={`${apiURL}storage/statutory/ceis.pdf`} target="_blank" rel="noreferrer" className="ea-link">CEIS</a>
                  )}
                  {a.status === "pending_signature" && (
                    <button className="ea-link" onClick={() => resend(a.id)}>Resend</button>
                  )}
                  {a.status === "employee_signed" && (
                    <button className="ea-link ea-link-primary" onClick={() => openCounterSign(a)}>Countersign</button>
                  )}
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      )}
      </>
      )}

      {/* ---- Countersign Modal ---- */}
      {counterSignTarget && (
        <div className="ea-modal-backdrop" onClick={() => setCounterSignTarget(null)}>
          <div className="ea-modal" onClick={(e) => e.stopPropagation()}>
            <h2>Countersign — {counterSignTarget.agreement_number}</h2>
            <p className="ea-modal-note">
              {counterSignTarget.employee_name} has signed. Sign below as Director / Operations Manager to fully
              execute this agreement — the final copy (with FWIS{counterSignTarget.ceis_attached ? " + CEIS" : ""})
              will be emailed to both the employee and admin automatically, and will appear here as "Fully Executed".
            </p>
            <label>Signature Name</label>
            <input value={employerSignatureName} onChange={(e) => setEmployerSignatureName(e.target.value)} placeholder="Full name" />
            <div className="ea-sig-header-bar">
              <label style={{ marginBottom: 0 }}>Sign Here</label>
              <div className="cs-sig-mode-toggle" role="tablist">
                <button
                  type="button"
                  role="tab"
                  aria-selected={counterSigMode === "draw"}
                  className={`cs-sig-tab-btn ${counterSigMode === "draw" ? "active" : ""}`}
                  onClick={() => setCounterSigMode("draw")}
                >
                  <i className="fa-solid fa-pen-nib" /><span>Draw Signature</span>
                </button>
                <button
                  type="button"
                  role="tab"
                  aria-selected={counterSigMode === "auto"}
                  className={`cs-sig-tab-btn ${counterSigMode === "auto" ? "active" : ""}`}
                  onClick={() => setCounterSigMode("auto")}
                >
                  <i className="fa-solid fa-wand-magic-sparkles" /><span>Auto-Generate</span>
                </button>
              </div>
            </div>
            <SignaturePad
              ref={sigPadRef}
              mode={counterSigMode}
              name={employerSignatureName}
              height={160}
              placeholderText={counterSigMode === "auto" ? "Enter your name above to generate signature" : "Draw your signature here"}
            />
            <div className="ea-modal-actions">
              <button className="ea-btn ea-btn-outline" onClick={() => setCounterSignTarget(null)}>Cancel</button>
              <button className="ea-btn ea-btn-solid" onClick={submitCounterSign} disabled={counterSigning}>
                {counterSigning ? "Submitting..." : "Countersign & Execute"}
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}