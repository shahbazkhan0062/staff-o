import React, { useState, useEffect, useRef, useCallback } from "react";
import { useSearchParams, Link } from "react-router-dom";
import { Helmet } from "react-helmet";
import { toast } from "react-toastify";
import staffoLogo from "../assets/images/staffo.png";
import Loader from "../components/Loader";
import SignaturePad from "../components/contracts/SignaturePad";
import { apiURL } from "../utils/exports";
// Reusing the existing contract-signing stylesheet — the "cs-*" classes are
// generic (card/badge/form layout), nothing contract-specific in the CSS
// itself, so there's no need for a near-duplicate file.
import "./ContractSign.css";

const formatStatus = (status = "") => {
  if (!status) return "Unknown";
  return status.split("_").map((w) => w.charAt(0).toUpperCase() + w.slice(1)).join(" ");
};

const EMPLOYMENT_TYPES = [
  { value: "casual", label: "Casual" },
  { value: "full_time", label: "Full-Time" },
  { value: "part_time", label: "Part-Time" },
];

export default function EmploymentAgreementSign() {
  const [searchParams] = useSearchParams();
  const token = searchParams.get("token") || "";

  const [loading, setLoading] = useState(true);
  const [agreement, setAgreement] = useState(null);
  const [fetchError, setFetchError] = useState(null);

  // Employee-editable fields — employment_type defaults to "casual" the
  // first time an agreement is sent (set server-side), everything else on
  // the page is read-only.
  const [employmentType, setEmploymentType] = useState("casual");
  const [classificationLevel, setClassificationLevel] = useState(1);
  const [guaranteedHours, setGuaranteedHours] = useState("");

  const [signatureName, setSignatureName] = useState("");
  const [signatureImage, setSignatureImage] = useState("");
  const [hasSignature, setHasSignature] = useState(false);
  const [sigMode, setSigMode] = useState("draw");
  const [validationErrors, setValidationErrors] = useState({});
  const [agreeTerms, setAgreeTerms] = useState(false);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [isSubmittedSuccessfully, setIsSubmittedSuccessfully] = useState(false);

  const sigPadRef = useRef(null);
  const [sigPadHeight, setSigPadHeight] = useState(() =>
    typeof window !== "undefined" && window.innerWidth < 480 ? 170 : 200
  );

  useEffect(() => {
    const handleResize = () => setSigPadHeight(window.innerWidth < 480 ? 170 : 200);
    window.addEventListener("resize", handleResize);
    return () => window.removeEventListener("resize", handleResize);
  }, []);

  const fetchAgreement = useCallback(async (authToken) => {
    if (!authToken) {
      setLoading(false);
      setFetchError("No agreement token was provided. Please check the link from your email.");
      return;
    }
    setLoading(true);
    setFetchError(null);
    try {
      const res = await fetch(`${apiURL}api/employment-agreement/sign/${authToken}`, {
        method: "GET",
        headers: { Accept: "application/json", "Content-Type": "application/json" },
      });
      const json = await res.json().catch(() => null);

      if (!res.ok || (json && json.success === false)) {
        setFetchError(json?.message || "Unable to load agreement details.");
        setAgreement(null);
        return;
      }
      if (json?.data) {
        setAgreement(json.data);
        setEmploymentType(json.data.employment_type || "casual");
        setClassificationLevel(json.data.classification_level || 1);
        setGuaranteedHours(json.data.guaranteed_hours || "");
        if (json.data.already_signed) setIsSubmittedSuccessfully(true);
      } else {
        setFetchError("Agreement data is unavailable.");
      }
    } catch (err) {
      console.error("Failed to fetch agreement:", err);
      setFetchError("Unable to connect to the server. Please check your internet connection and try again.");
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    fetchAgreement(token);
  }, [token, fetchAgreement]);

  const handleSignatureChange = ({ isEmpty, dataUrl }) => {
    setHasSignature(!isEmpty);
    setSignatureImage(dataUrl || "");
    if (!isEmpty && validationErrors.signature) {
      setValidationErrors((prev) => ({ ...prev, signature: null }));
    }
  };

  const validateForm = () => {
    const errors = {};
    if (!signatureName.trim()) errors.signatureName = "Please enter your legal name.";

    const currentSigData = sigPadRef.current?.toDataURL() || signatureImage;
    const isSigEmpty = sigPadRef.current?.isEmpty ? sigPadRef.current.isEmpty() : !hasSignature;
    if (isSigEmpty || !currentSigData) {
      errors.signature = "Please provide your signature by entering your name above.";
    }
    if (employmentType === "part_time" && !guaranteedHours.trim()) {
      errors.guaranteedHours = "Part-Time employment needs guaranteed hours per week (Fair Work Act requirement).";
    }
    if (!agreeTerms) {
      errors.agreeTerms = "Please confirm you've read and agree to the Employment Agreement to proceed.";
    }
    setValidationErrors(errors);
    return Object.keys(errors).length === 0;
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    if (isSubmitting) return;
    if (!validateForm()) {
      toast.error(
        !agreeTerms
          ? "Please confirm you agree to the Employment Agreement."
          : "Please fill in all required fields."
      );
      return;
    }
    const finalBase64 = sigPadRef.current?.toDataURL() || signatureImage;
    if (!finalBase64) {
      toast.error("Please provide your signature.");
      return;
    }

    setIsSubmitting(true);
    try {
      const res = await fetch(`${apiURL}api/employment-agreement/sign/${token}`, {
        method: "POST",
        headers: { Accept: "application/json", "Content-Type": "application/json" },
        body: JSON.stringify({
          signature_name: signatureName.trim(),
          signature_image: finalBase64,
          employment_type: employmentType,
          classification_level: classificationLevel,
          guaranteed_hours: employmentType === "part_time" ? guaranteedHours.trim() : null,
        }),
      });
      const json = await res.json().catch(() => null);

      if (!res.ok || (json && json.success === false)) {
        toast.error(json?.message || "Failed to submit signature. Please try again.");
        return;
      }

      toast.success(json?.message || "Signed successfully!");
      setIsSubmittedSuccessfully(true);
    } catch (err) {
      console.error("Submission failed:", err);
      toast.error("An unexpected network error occurred. Please try again.");
    } finally {
      setIsSubmitting(false);
    }
  };

  const getStatusBadgeClass = (status = "") => {
    const s = status.toLowerCase();
    if (s.includes("pending")) return "cs-badge-pending";
    if (s.includes("executed") || s.includes("signed")) return "cs-badge-signed";
    return "cs-badge-pending";
  };

  return (
    <>
      <Helmet>
        <title>Casual Employment Agreement | Staffoo</title>
        <meta name="description" content="Review and digitally sign your Staffoo employment agreement securely online." />
        <link rel="icon" type="image/png" href="/staffo.png" />
      </Helmet>

      <div className="cs-page">
        <header className="cs-topbar">
          <div className="cs-container cs-topbar-inner">
            <Link to="/" className="cs-topbar-brand" title="Staffoo Home">
              <img src={staffoLogo} alt="Staffoo" className="cs-topbar-logo" />
            </Link>
            <div className="cs-topbar-badge">
              <i className="fa-solid fa-shield-halved" />
              <span>Secure Digital Signature Portal</span>
            </div>
          </div>
        </header>

        <main className="cs-main">
          <div className="cs-container">
            {loading && (
              <div style={{ padding: "60px 0" }}>
                <Loader message="Loading your employment agreement..." />
              </div>
            )}

            {!loading && fetchError && (
              <div className="cs-error-card">
                <div className="cs-error-icon-wrap"><i className="fa-solid fa-triangle-exclamation" /></div>
                <h2 className="cs-error-title">Unable to Load Agreement</h2>
                <p className="cs-error-desc">{fetchError}</p>
                <div className="cs-action-btns">
                  <button type="button" onClick={() => fetchAgreement(token)} className="nh-btn nh-btn-solid" style={{ padding: "10px 24px" }}>
                    <i className="fa-solid fa-rotate-right" style={{ marginRight: "8px" }} />
                    Retry
                  </button>
                  <Link to="/" className="nh-btn nh-btn-outline" style={{ padding: "10px 24px" }}>Return to Home</Link>
                </div>
              </div>
            )}

            {!loading && !fetchError && isSubmittedSuccessfully && (
              <div className="cs-success-card">
                <div className="cs-success-icon-wrap"><i className="fa-solid fa-check" /></div>
                <h2 className="cs-success-title">
                  {agreement?.fully_executed ? "Employment Agreement Fully Executed" : "Signature Submitted"}
                </h2>
                <p className="cs-success-desc">
                  {agreement?.fully_executed
                    ? "Your employment agreement has been signed by both parties and emailed to you with the Fair Work Information Statement (and Casual Employment Information Statement, if applicable)."
                    : "Thanks — your signature has been recorded. Staffoo's Operations Manager still needs to countersign; you'll receive the fully executed copy by email once that's done."}
                </p>
                {agreement && (
                  <div className="cs-success-summary">
                    <div className="cs-success-summary-row">
                      <span className="cs-success-summary-label">Agreement Number:</span>
                      <span className="cs-success-summary-value">{agreement.agreement_number}</span>
                    </div>
                    <div className="cs-success-summary-row">
                      <span className="cs-success-summary-label">Signed By:</span>
                      <span className="cs-success-summary-value">{signatureName || agreement.employee_name}</span>
                    </div>
                    <div className="cs-success-summary-row">
                      <span className="cs-success-summary-label">Employment Category:</span>
                      <span className="cs-success-summary-value">
                        {EMPLOYMENT_TYPES.find((t) => t.value === employmentType)?.label}
                      </span>
                    </div>
                  </div>
                )}
                <div className="cs-action-btns">
                  {agreement?.pdf_url && (
                    <a href={agreement.pdf_url} target="_blank" rel="noopener noreferrer" className="nh-btn nh-btn-outline" style={{ padding: "12px 28px" }}>
                      View Signed Copy
                    </a>
                  )}
                  <Link to="/" className="nh-btn nh-btn-solid" style={{ padding: "12px 28px" }}>Go to Homepage</Link>
                </div>
              </div>
            )}

            {!loading && !fetchError && !isSubmittedSuccessfully && agreement && (
              <>
                <div className="cs-hero-section">
                  <h1 className="cs-title">Casual Employment Agreement</h1>
                  <p className="cs-subtitle">
                    Please review your details below, choose your employment category and classification, then sign to accept your employment with Staffoo.
                  </p>
                </div>

                <div className="cs-card">
                  <div className="cs-card-header">
                    <h2 className="cs-card-title">
                      <i className="fa-solid fa-file-lines" />
                      Comprehensive Security Staff Employment Agreement
                    </h2>
                    <span className={`cs-badge ${getStatusBadgeClass(agreement.status)}`}>
                      <span className="cs-badge-dot" />
                      {formatStatus(agreement.status)}
                    </span>
                  </div>
                  <p className="cs-sig-instruction" style={{ marginBottom: 16 }}>
                    These details were filled in automatically from your registration — review them before signing.
                  </p>
                  <div className="cs-grid">
                    <div className="cs-grid-item">
                      <span className="cs-grid-label">Agreement Number</span>
                      <span className="cs-grid-value highlight">{agreement.agreement_number}</span>
                    </div>
                    <div className="cs-grid-item">
                      <span className="cs-grid-label">Employee</span>
                      <span className="cs-grid-value">{agreement.employee_name}</span>
                    </div>
                    <div className="cs-grid-item">
                      <span className="cs-grid-label">Address</span>
                      <span className="cs-grid-value">{agreement.employee_address || "—"}</span>
                    </div>
                    <div className="cs-grid-item">
                      <span className="cs-grid-label">Phone / Email</span>
                      <span className="cs-grid-value">{agreement.employee_phone || "—"} · {agreement.employee_email || "—"}</span>
                    </div>
                    <div className="cs-grid-item">
                      <span className="cs-grid-label">State Security Licence #</span>
                      <span className="cs-grid-value">{agreement.security_license_no || "—"}</span>
                    </div>
                    <div className="cs-grid-item">
                      <span className="cs-grid-label">Commencement Date</span>
                      <span className="cs-grid-value">{agreement.commencement_date}</span>
                    </div>
                  </div>
                </div>

                {/* ---- The two fields the employee actually sets ---- */}
                <div className="cs-card">
                  <div className="cs-card-header">
                    <h2 className="cs-card-title"><i className="fa-solid fa-sliders" />Your Employment Details</h2>
                  </div>
                  <p className="cs-sig-instruction">
                    Confirm your award classification level below. Your Category of Employment is{" "}
                    <strong>Casual</strong> — this is fixed and can't be changed from this page; contact Staffoo if
                    that's not correct.
                  </p>

                  <div className="cs-form-group">
                    <label className="cs-form-label">Category of Employment</label>
                    <div
                      className="cs-sig-tab-btn active"
                      style={{ width: "100%", justifyContent: "center", cursor: "default", pointerEvents: "none" }}
                    >
                      <span>Casual</span>
                    </div>
                  </div>

                  <div className="cs-form-group">
                    <label htmlFor="classification" className="cs-form-label">
                      Award Classification Level <span className="required">*</span>
                    </label>
                    <select
                      id="classification"
                      className="cs-input"
                      value={classificationLevel}
                      onChange={(e) => setClassificationLevel(Number(e.target.value))}
                      disabled={isSubmitting}
                    >
                      {[1, 2, 3, 4, 5].map((l) => (
                        <option key={l} value={l}>Security Officer Level {l}</option>
                      ))}
                    </select>
                  </div>

                  {employmentType === "part_time" && (
                    <div className="cs-form-group">
                      <label htmlFor="guaranteedHours" className="cs-form-label">
                        Guaranteed Hours per Week <span className="required">*</span>
                      </label>
                      <input
                        id="guaranteedHours"
                        className={`cs-input ${validationErrors.guaranteedHours ? "has-error" : ""}`}
                        type="text"
                        placeholder='e.g. "20 hours per week"'
                        value={guaranteedHours}
                        onChange={(e) => {
                          setGuaranteedHours(e.target.value);
                          if (validationErrors.guaranteedHours) setValidationErrors((p) => ({ ...p, guaranteedHours: null }));
                        }}
                        disabled={isSubmitting}
                      />
                      {validationErrors.guaranteedHours && (
                        <div className="cs-field-error"><i className="fa-solid fa-circle-exclamation" />{validationErrors.guaranteedHours}</div>
                      )}
                    </div>
                  )}
                  {employmentType === "casual" && (
                    <p className="cs-sig-instruction" style={{ marginBottom: 0 }}>
                      Casual — no guaranteed hours; you'll also receive the Casual Employment Information Statement below.
                    </p>
                  )}
                </div>

                {agreement.pdf_url && (
                  <div className="cs-card">
                    <div className="cs-card-header">
                      <h2 className="cs-card-title"><i className="fa-solid fa-file-pdf" style={{ color: "#DC2626" }} />Agreement Document</h2>
                    </div>
                    <div className="cs-doc-box">
                      <div className="cs-doc-info">
                        <div className="cs-doc-icon"><i className="fa-solid fa-file-pdf" /></div>
                        <div className="cs-doc-text">
                          <div className="cs-doc-title"><span className="cs-doc-filename">{agreement.agreement_number}.pdf</span></div>
                          <div className="cs-doc-meta">Includes Schedule 1 (your details) and Schedule 2 (pay rates). Opens in a new tab.</div>
                        </div>
                      </div>
                      <a href={agreement.pdf_url} target="_blank" rel="noopener noreferrer" className="cs-pdf-btn">
                        <i className="fa-solid fa-file-pdf" /><span>View Agreement PDF</span>
                        <i className="fa-solid fa-arrow-up-right-from-square" style={{ fontSize: "12px", opacity: 0.85 }} />
                      </a>
                    </div>
                  </div>
                )}

                {/* Statutory attachments — FWIS always, CEIS only while Casual is selected */}
                {(agreement.fwis_url || employmentType === "casual") && (
                  <div className="cs-card">
                    <div className="cs-card-header">
                      <h2 className="cs-card-title"><i className="fa-solid fa-scale-balanced" />Statutory Attachments</h2>
                    </div>
                    <p className="cs-sig-instruction" style={{ marginBottom: 12 }}>
                      Required by the Fair Work Act 2009 (Cth) alongside your employment agreement:
                    </p>
                    {agreement.fwis_url && (
                      <div className="cs-doc-box" style={{ marginBottom: employmentType === "casual" ? 10 : 0 }}>
                        <div className="cs-doc-info">
                          <div className="cs-doc-icon"><i className="fa-solid fa-file-pdf" /></div>
                          <div className="cs-doc-text">
                            <div className="cs-doc-title"><span className="cs-doc-filename">Fair Work Information Statement</span></div>
                          </div>
                        </div>
                        <a href={agreement.fwis_url} target="_blank" rel="noopener noreferrer" className="cs-pdf-btn">
                          <i className="fa-solid fa-file-pdf" /><span>View</span>
                        </a>
                      </div>
                    )}
                    {employmentType === "casual" && agreement.ceis_url && (
                      <div className="cs-doc-box">
                        <div className="cs-doc-info">
                          <div className="cs-doc-icon"><i className="fa-solid fa-file-pdf" /></div>
                          <div className="cs-doc-text">
                            <div className="cs-doc-title"><span className="cs-doc-filename">Casual Employment Information Statement</span></div>
                          </div>
                        </div>
                        <a href={agreement.ceis_url} target="_blank" rel="noopener noreferrer" className="cs-pdf-btn">
                          <i className="fa-solid fa-file-pdf" /><span>View</span>
                        </a>
                      </div>
                    )}
                  </div>
                )}

                <form onSubmit={handleSubmit} className="cs-card" noValidate>
                  <div className="cs-card-header">
                    <h2 className="cs-card-title"><i className="fa-solid fa-file-signature" />Digital Signature</h2>
                  </div>
                  <p className="cs-sig-instruction">
                    Enter your legal name, then draw your signature (or choose Auto-Generate). By signing, you confirm
                    you've read and accept the Employment Agreement above — including the employment category and
                    classification you selected — and acknowledge receipt of the statutory documents.
                  </p>

                  <div className="cs-form-group">
                    <label htmlFor="signature_name" className="cs-form-label">Signature Name <span className="required">*</span></label>
                    <input
                      type="text" id="signature_name" name="signature_name" placeholder="Enter your full name"
                      value={signatureName}
                      onChange={(e) => {
                        setSignatureName(e.target.value);
                        if (validationErrors.signatureName) setValidationErrors((p) => ({ ...p, signatureName: null }));
                      }}
                      className={`cs-input ${validationErrors.signatureName ? "has-error" : ""}`}
                      disabled={isSubmitting}
                      autoComplete="name"
                    />
                    {validationErrors.signatureName && (
                      <div className="cs-field-error"><i className="fa-solid fa-circle-exclamation" />{validationErrors.signatureName}</div>
                    )}
                  </div>

                  <div className="cs-form-group">
                    <div className="cs-sig-header-bar">
                      <label className="cs-form-label" style={{ marginBottom: 0 }}>Sign Here <span className="required">*</span></label>
                      <div className="cs-sig-mode-toggle" role="tablist">
                        <button type="button" role="tab" aria-selected={sigMode === "draw"} className={`cs-sig-tab-btn ${sigMode === "draw" ? "active" : ""}`} onClick={() => setSigMode("draw")}>
                          <i className="fa-solid fa-pen-nib" /><span>Draw Signature</span>
                        </button>
                        <button type="button" role="tab" aria-selected={sigMode === "auto"} className={`cs-sig-tab-btn ${sigMode === "auto" ? "active" : ""}`} onClick={() => setSigMode("auto")}>
                          <i className="fa-solid fa-wand-magic-sparkles" /><span>Auto-Generate</span>
                        </button>
                      </div>
                    </div>
                    <SignaturePad
                      ref={sigPadRef}
                      mode={sigMode}
                      name={signatureName}
                      height={sigPadHeight}
                      onChange={handleSignatureChange}
                      disabled={isSubmitting}
                      placeholderText={sigMode === "auto" ? "Enter your name above to generate signature" : "Draw your signature here"}
                    />
                    {validationErrors.signature && (
                      <div className="cs-field-error"><i className="fa-solid fa-circle-exclamation" />{validationErrors.signature}</div>
                    )}
                  </div>

                  <div className={`cs-terms-checkbox-group ${validationErrors.agreeTerms ? "has-error" : ""}`}>
                    <label className="cs-terms-checkbox-label">
                      <input
                        type="checkbox" checked={agreeTerms}
                        onChange={(e) => {
                          setAgreeTerms(e.target.checked);
                          if (validationErrors.agreeTerms) setValidationErrors((p) => ({ ...p, agreeTerms: null }));
                        }}
                        disabled={isSubmitting}
                        className="cs-checkbox"
                      />
                      <span>
                        I have read and agree to the Employment Agreement — as a{" "}
                        <strong>{EMPLOYMENT_TYPES.find((t) => t.value === employmentType)?.label}</strong> employee,
                        Level {classificationLevel} — and acknowledge receipt of the Fair Work Information Statement
                        {employmentType === "casual" ? " and the Casual Employment Information Statement" : ""}.
                        <span className="required" style={{ color: "#DC2626" }}> *</span>
                      </span>
                    </label>
                    {validationErrors.agreeTerms && (
                      <div className="cs-field-error"><i className="fa-solid fa-circle-exclamation" />{validationErrors.agreeTerms}</div>
                    )}
                  </div>

                  <div className="cs-submit-box">
                    <button
                      type="submit" disabled={isSubmitting || !agreeTerms} className="cs-submit-btn"
                      style={{ opacity: !agreeTerms || isSubmitting ? 0.65 : 1, cursor: !agreeTerms || isSubmitting ? "not-allowed" : "pointer" }}
                    >
                      {isSubmitting ? (<><i className="fa-solid fa-circle-notch fa-spin" />Submitting Signature...</>) : (<><i className="fa-solid fa-signature" />Sign &amp; Submit</>)}
                    </button>
                    <p className="cs-submit-disclaimer">Your digital signature is legally binding and timestamped under applicable electronic transaction laws.</p>
                  </div>
                </form>
              </>
            )}
          </div>
        </main>

        <footer className="cs-simple-footer">
          <div className="cs-container"><p>© {new Date().getFullYear()} Staffoo. All rights reserved.</p></div>
        </footer>
      </div>
    </>
  );
}