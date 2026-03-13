import React from "react";
import { createRoot } from "react-dom/client";

const container = document.getElementById("react-homepage");
if (container) {
    createRoot(container).render(<h1>React it's working!</h1>);
}