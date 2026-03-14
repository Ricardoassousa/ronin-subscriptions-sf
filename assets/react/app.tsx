import React from "react";
import { createRoot } from "react-dom/client";

const App: React.FC = () => {
    return <h1>React + TypeScript</h1>;
};

const container = document.getElementById("react-homepage")!;
const root = createRoot(container);
root.render(<App />);