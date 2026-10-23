export function LoadingScreen({ message }: { message?: string }) {
  return (
    <div className="loading-screen">
      <div className="spinner" aria-hidden="true" />
      <p>{message ?? "Loading..."}</p>
    </div>
  );
}
