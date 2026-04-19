// graphify OpenCode plugin
// Adds automatic graphify context for codebase questions in this project.
import { existsSync } from "fs";
import { join } from "path";
import { execFileSync } from "child_process";

const GRAPH_PATH = ["graphify-out", "graph.json"];
const QUERY_BUDGET = "1400";

function extractUserText(parts) {
  if (!Array.isArray(parts)) return "";
  return parts
    .filter((p) => p?.type === "text" && typeof p.text === "string")
    .map((p) => p.text.trim())
    .filter(Boolean)
    .join("\n\n")
    .trim();
}

function shouldUseGraphify(text) {
  if (!text || text.length < 8) return false;

  const t = text.toLowerCase();
  if (t.startsWith("no-graphify:")) return false;
  if (t.includes("ignore graphify")) return false;

  const patterns = [
    /^\s*(what|how|where|why|which|show|list|find|explain)\b/,
    /\?/,
    /\b(codebase|architecture|module|component|function|class|method|file|path|flow|dependency|schema|table|query|bug|error|stack trace|refactor|implement|history|diff)\b/,
  ];

  return patterns.some((r) => r.test(t));
}

function runGraphifyQuery(directory, question) {
  try {
    const graphPath = join(directory, ...GRAPH_PATH);
    const result = execFileSync(
      "graphify",
      ["query", question, "--budget", QUERY_BUDGET, "--graph", graphPath],
      {
        cwd: directory,
        encoding: "utf8",
        maxBuffer: 8 * 1024 * 1024,
      },
    );

    return typeof result === "string" ? result.trim() : "";
  } catch {
    return "";
  }
}

export const GraphifyPlugin = async ({ directory }) => {
  let reminderShown = false;
  let lastMessageID = "";

  return {
    "chat.message": async (input, output) => {
      const graphPath = join(directory, ...GRAPH_PATH);
      if (!existsSync(graphPath)) return;

      if (input.messageID && input.messageID === lastMessageID) return;

      const userText = extractUserText(output.parts);
      if (!shouldUseGraphify(userText)) return;

      const graphContext = runGraphifyQuery(directory, userText);
      if (!graphContext) return;

      const injected =
        "[graphify auto-context]\n" +
        graphContext +
        "\n[/graphify auto-context]\n" +
        "Use this graph context as the primary memory source for this request.";

      output.message.system = output.message.system
        ? `${output.message.system}\n\n${injected}`
        : injected;

      if (input.messageID) lastMessageID = input.messageID;
    },

    "tool.execute.before": async (input, output) => {
      if (reminderShown) return;
      if (input.tool !== "bash") return;
      if (!existsSync(join(directory, ...GRAPH_PATH))) return;

      output.args.command =
        'echo "[graphify] Auto-context enabled for codebase queries in this project." && ' +
        output.args.command;
      reminderShown = true;
    },
  };
};
