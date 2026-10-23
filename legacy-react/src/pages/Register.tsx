import { useState, type FormEvent } from "react";
import { Link, Navigate } from "react-router-dom";
import { supabase } from "../lib/supabase";
import { useAuth } from "../context/AuthContext";
import { LoadingScreen } from "../components/LoadingScreen";

const EMAIL_RE = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

function validate(
  fullName: string,
  email: string,
  password: string,
  confirm: string,
): Record<string, string> {
  const errors: Record<string, string> = {};
  if (!fullName.trim()) errors.fullName = "Full name is required.";
  if (!email.trim()) {
    errors.email = "Email is required.";
  } else if (!EMAIL_RE.test(email.trim())) {
    errors.email = "Enter a valid email address.";
  }
  if (!password) {
    errors.password = "Password is required.";
  } else if (password.length < 8) {
    errors.password = "Password must be at least 8 characters.";
  } else if (!/[A-Za-z]/.test(password) || !/[0-9]/.test(password)) {
    errors.password = "Password must contain at least one letter and one number.";
  }
  if (confirm !== password) {
    errors.confirm = "Passwords do not match.";
  }
  return errors;
}

export function RegisterPage() {
  const { session, loading } = useAuth();
  const [fullName, setFullName] = useState("");
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [confirm, setConfirm] = useState("");
  const [errors, setErrors] = useState<Record<string, string>>({});
  const [formError, setFormError] = useState<string | null>(null);
  const [submitting, setSubmitting] = useState(false);
  const [done, setDone] = useState<null | "confirm_email" | "pending">(null);

  if (loading) return <LoadingScreen />;
  if (session && !done) return <Navigate to="/" replace />;

  async function handleSubmit(e: FormEvent) {
    e.preventDefault();
    setFormError(null);

    const nextErrors = validate(fullName, email, password, confirm);
    setErrors(nextErrors);
    if (Object.keys(nextErrors).length > 0) return;

    setSubmitting(true);
    const { data, error } = await supabase.auth.signUp({
      email: email.trim(),
      password,
      options: {
        data: { full_name: fullName.trim() },
      },
    });
    setSubmitting(false);

    if (error) {
      const msg = /already registered|already exists/i.test(error.message)
        ? "An account with this email already exists. Try logging in instead."
        : error.message;
      setFormError(msg);
      return;
    }

    setDone(data.session ? "pending" : "confirm_email");
  }

  if (done) {
    return (
      <div className="center-layout">
        <div className="card notice notice--warning">
          <div className="notice__badge notice__badge--warning" aria-hidden="true">
            ✓
          </div>
          <h1 className="notice__title">
            {done === "confirm_email"
              ? "Confirm your email"
              : "Registration received"}
          </h1>
          <p className="notice__message">
            {done === "confirm_email"
              ? "We sent a confirmation link to your email. Confirm it, then wait for organizer approval."
              : "Your account was created with a pending status. An organizer must approve it before you can scan."}
          </p>
          <Link to="/login" className="btn btn--primary btn--block">
            Go to login
          </Link>
        </div>
      </div>
    );
  }

  return (
    <div className="center-layout">
      <div className="card">
        <header className="auth-header">
          <h1 className="auth-title">Clerk Registration</h1>
          <p className="auth-subtitle">
            Create an account to request scanning access.
          </p>
        </header>

        {formError && (
          <div className="alert alert--error" role="alert">
            {formError}
          </div>
        )}

        <form onSubmit={handleSubmit} noValidate>
          <div className="field">
            <label htmlFor="fullName">Full name</label>
            <input
              id="fullName"
              type="text"
              autoComplete="name"
              value={fullName}
              onChange={(e) => setFullName(e.target.value)}
              aria-invalid={!!errors.fullName}
            />
            {errors.fullName && <p className="field-error">{errors.fullName}</p>}
          </div>

          <div className="field">
            <label htmlFor="email">Email address</label>
            <input
              id="email"
              type="email"
              inputMode="email"
              autoComplete="email"
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              aria-invalid={!!errors.email}
            />
            {errors.email && <p className="field-error">{errors.email}</p>}
          </div>

          <div className="field">
            <label htmlFor="password">Password</label>
            <input
              id="password"
              type="password"
              autoComplete="new-password"
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              aria-invalid={!!errors.password}
            />
            {errors.password && <p className="field-error">{errors.password}</p>}
          </div>

          <div className="field">
            <label htmlFor="confirm">Confirm password</label>
            <input
              id="confirm"
              type="password"
              autoComplete="new-password"
              value={confirm}
              onChange={(e) => setConfirm(e.target.value)}
              aria-invalid={!!errors.confirm}
            />
            {errors.confirm && <p className="field-error">{errors.confirm}</p>}
          </div>

          <button
            type="submit"
            className="btn btn--primary btn--block"
            disabled={submitting}
          >
            {submitting ? "Registering..." : "Register"}
          </button>
        </form>

        <p className="auth-footer">
          Already registered? <Link to="/login">Log in</Link>
        </p>
      </div>
    </div>
  );
}
