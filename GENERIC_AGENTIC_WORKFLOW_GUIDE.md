# 🏢 Generic Agentic Workflow: Professional Multi-Agent OS Setup Guide

## 📖 Title & Overview

**Title:** 🏢 Generic Agentic Workflow: Professional Multi-Agent OS Setup Guide

**Overview:** A complete software house simulation where YOU are the CEO, and agents work as professional employees on ANY project. This guide provides a repeatable, project-agnostic framework for setting up a professional multi-agent operating system using DeepSeek Harness (DSH) Web GUI.

**Core Architecture:**
```
YOU (CEO)
    ↓
Agent Teams (PM, BA, Architect, Devs, QA, etc.)
    ↓
Workflow Engine (dsh-agent-bus with DAG orchestration)
    ↓
Memory System (7 layers: soul → rules)
    ↓
Project Documentation (5 mandatory files)
```

**Your Role:** As the CEO, you set the vision, approve plans, and review documentation. Agent teams execute the work under your guidance, using standardized processes and documentation that remain constant regardless of project type.

---

## 📄 Project Documentation Standard

Every project **MUST** have these 5 essential documentation files. They serve as the single source of truth for all agents.

### a) PRD.md (Product Requirements Document)
- **What:** Project overview, goals, scope, features, user stories, success metrics
- **Purpose:** Single source of truth for **WHAT** the project is about
- **Agent Usage:** PM and Business Analyst use this to understand requirements

### b) Architecture.md (Technical Architecture)
- **What:** System architecture, tech stack, data flow, component diagrams, ERD
- **Purpose:** Single source of truth for **HOW** the project is built
- **Agent Usage:** Solution Architect and Developers use this for implementation

### c) Decisions.md (Architectural Decisions)
- **What:** All technical decisions made, with rationales and alternatives considered
- **Purpose:** Track **WHY** certain choices were made
- **Agent Usage:** All agents reference this to maintain consistency

### d) Tasks.md (Task Management)
- **What:** All tasks, their status, dependencies, assignees, and acceptance criteria
- **Purpose:** Track **WHAT** is being worked on, by **WHOM**, and **WHAT's** left
- **Agent Usage:** PM creates/updates, all agents reference for their work

### e) AGENTS.md (Agent Instructions)
- **What:** Instructions for agents on how to work on this project
- **Purpose:** Tells agents **HOW** to behave for this specific project
- **Agent Usage:** All agents follow these instructions

---

## 🚀 Initial Project Setup Workflow

### Scenario A: Project Documentation Exists
- Admin provides links/paths to the 5 documentation files
- Agents read and understand the project context
- Team starts working immediately
- Memory system stores project facts from documentation

### Scenario B: No Documentation Exists (New Project)
> **Admin says:** "I want to build [PROJECT_TYPE] with [KEY_FEATURES]"

**Agentic Workflow Asks Clarifying Questions:**
1. What are the main features?
2. What technologies do you prefer?
3. Who are the target users?
4. What's the timeline?

**Based on Answers, Agents CREATE the 5 Documentation Files:**
- PM/BA creates **PRD.md**
- Solution Architect creates **Architecture.md** with ERD mermaid diagrams
- Team creates **Decisions.md**, **Tasks.md**, **AGENTS.md**

**Admin Reviews and Approves all Documents**
- Workflow begins after approval

> **⚠️ Warning:** Never start work without approved documentation. The memory system cannot store context without proper project anchors.

---

## 💿 Complete Installation Steps (Web GUI Only)

**No plugin installation needed.** The following tools are built into the DeepSeek Harness Web GUI:

- **Memory:** `memory_save`, `memory_list`, `memory_search`, `memory_confirm`, `memory_forget`, `memory_update`
- **Agent Teams:** `agent_teams_create`, `agent_teams_add_member`, `agent_teams_create_task`, `agent_teams_status`, `agent_teams_send_message`, `agent_teams_update_task`, `agent_teams_reassign_task`
- **Workflow:** `create_flow`, `create_task`, `submit_handoff`, `list_tasks`, `get_task`, `settle_task`, `report_task`

---

## 🛠️ Phase 0: Project Setup (Required for Multi-Project Support)

**Do this ONCE per project. Sets up project isolation and shell helpers.**

### Step 0.1: Define Your Project Values

Replace these placeholders with your actual values:

| Placeholder | Description | Example |
|-------------|-------------|---------|
| `[PROJECT_NAME]` | Your project name | `task_manager`, `ecommerce_app`, `my_project` |
| `[PROJECT_TYPE]` | Type of software | `web app`, `desktop app`, `mobile app`, `API`, `SaaS` |
| `[KEY_FEATURES]` | Main features (comma-separated) | `user, role, auth, ...` |
| `[TEAM_NAME]` | Your agent team name | `alpha_team`, `dev_squad`, `production_team` |
| `[TECH_STACK]` | Tech stack summary | `Laravel + Vue/Nuxt + SQLite + TypeScript`, `React + Node.js + PostgreSQL` |
| `[PROJECT_SCOPE]` | Scope description | `single-station, offline-first, desktop+web`, `multi-tenant, cloud` |

---

### Step 0.2: Create Project Configuration (.dshrc)

In your project root directory:

```bash
cd /path/to/your/project

# Create .dshrc with YOUR values (replace all [PLACEHOLDERS])
cat > .dshrc << 'EOF'

# DSH Project Configuration — REPLACE THESE VALUES
export DSH_PROJECT_NAME="[PROJECT_NAME]"        # e.g., my_project
export DSH_PROJECT_TYPE="[PROJECT_TYPE]"        # e.g., desktop+web hybrid
export DSH_TEAM_NAME="[TEAM_NAME]"              # e.g., production_team
export DSH_PROJECT_ROOT="$(pwd)"
export DSH_MEMORY_LAYER="project"
export DSH_PROJECT_ANCHOR="true"
export DSH_MEMORY_WORKFLOW="suggested"
export DSH_DEFAULT_MODEL="nemotron-3-ultra-free"

# Project-specific context (used in Web GUI memory_save commands)
export KEY_FEATURES="[KEY_FEATURES]"        # e.g., user, role, auth, ...
export TECH_STACK="[TECH_STACK]"            # e.g., Laravel + Vue/Nuxt + SQLite + TypeScript
export PROJECT_SCOPE="[PROJECT_SCOPE]"      # e.g., single-station, offline-first, desktop+web
EOF
```

> **Note:** This file stays in your project root. Each project has its own `.dshrc`. Do NOT commit this file if it contains secrets.

---

### Step 0.3: Create Project Loader Script (~/.dsh/load-project.sh)

Run **ONCE on your machine** (not per project):

```bash
mkdir -p ~/.dsh
cat > ~/.dsh/load-project.sh << 'EOF'
#!/bin/bash
# Load DSH project configuration from nearest .dshrc

find_dshrc() {
    local dir="$(pwd)"
    while [[ "$dir" != "/" ]]; do
        if [[ -f "$dir/.dshrc" ]]; then
            echo "$dir/.dshrc"
            return 0
        fi
        dir="$(dirname "$dir")"
    done
    return 1
}

DSHRC=$(find_dshrc)
if [[ -n "$DSHRC" ]]; then
    echo "📁 Loading project: $(basename "$(dirname "$DSHRC")")"
    source "$DSHRC"
    
    export DSH_PROJECT_LOADED=true
    dsh web
else
    echo "⚠️  No .dshrc found in current directory"
    export DSH_PROJECT_NAME="default"
    export DSH_PROJECT_LOADED=false
fi
EOF

chmod +x ~/.dsh/load-project.sh
```

---

### Step 0.4: Add Shell Aliases (~/.bashrc or ~/.zshrc)

Run **ONCE on your machine**:

```bash
cat >> ~/.bashrc << 'EOF'

## DeepSeek Harness 
# DSH Multi-Project Aliases
alias dsh-load='source ~/.dsh/load-project.sh'
alias dsh-web='dsh-load && dsh web'
alias dsh-cmd='dsh-load && dsh'

# Quick project switcher — switches to project dir and loads config
function dsh-project() {
    PROJECT_DIR="/var/www/html"  # Set your projects directory

    if [ -z "$1" ]; then
        echo "Usage: dsh-project <project-name>"
        echo "Projects in $PROJECT_DIR/:"
        ls -d $PROJECT_DIR/*/ 2>/dev/null | xargs -n1 basename
        return
    fi
    
    PROJECT_DIR="$PROJECT_DIR/$1"
    if [ -d "$PROJECT_DIR" ]; then
        cd "$PROJECT_DIR"
        echo "📁 Switched to project: $1"
        dsh-load
    else
        echo "❌ Project not found: $1"
        ls -d $PROJECT_DIR/*/ 2>/dev/null | xargs -n1 basename
    fi
}

# Quick memory helpers (require dsh-load first)
function dsh-memory-add() { dsh-load && dsh memory add --profile web --project "$DSH_PROJECT_NAME" "$@"; }
function dsh-memory-recall() { dsh-load && dsh memory recall --profile web --project "$DSH_PROJECT_NAME" "$@"; }
function dsh-memory-list() { dsh-load && dsh memory list --profile web --project "$DSH_PROJECT_NAME" "$@"; }
EOF

source ~/.bashrc
```

---

### Step 0.5: Load Your Project

Now switch to your project and load its config:

```bash
# Go to your project and load config
cd /path/to/your/project
dsh-project [PROJECT_NAME]    # or just: dsh-load

# Verify environment variables are set
echo "Project: $DSH_PROJECT_NAME"
echo "Type: $DSH_PROJECT_TYPE"
echo "Team: $DSH_TEAM_NAME"
```

**After this, your environment variables are available for ALL subsequent steps.**

---

## 🌐 Web GUI: In-Chat Tools Workflow

### Step 1: Configure Project Context (In-Chat)

Uses env vars from `.dshrc` loaded in Phase 0:

```bash
# Save project identity (soul layer) — uses $DSH_PROJECT_NAME, $DSH_PROJECT_TYPE
memory_save --content "Project: $DSH_PROJECT_NAME - $DSH_PROJECT_TYPE with $DSH_KEY_FEATURES" --namespace project --keywords "$DSH_PROJECT_NAME,identity,project" --status suggested

# Save technical facts (project layer) — uses $DSH_PROJECT_NAME, $DSH_TECH_STACK, $DSH_PROJECT_SCOPE
memory_save --content "Tech stack: $DSH_TECH_STACK. Scope: $DSH_PROJECT_SCOPE" --namespace project --keywords "$DSH_PROJECT_NAME,tech-stack,architecture" --status suggested

# Save project type and team
memory_save --content "Project type: $DSH_PROJECT_TYPE. Team: $DSH_TEAM_NAME. CEO: $(whoami)" --namespace project --keywords "$DSH_PROJECT_NAME,team,project-type" --status suggested
```

> **Note:** `$DSH_KEY_FEATURES`, `$DSH_TECH_STACK`, `$DSH_PROJECT_SCOPE` are shell variables you define locally, or replace with actual values directly in the command.

---

### Step 2: Initialize Agent Team (In-Chat)

```bash
# Create team (you are captain/CEO)
agent_teams_create --name "$DSH_PROJECT_NAME_team" --description "Multi-agent team for $DSH_PROJECT_NAME development"

# Add standard team members
agent_teams_add_member --name "pm" --role "Project Manager" --description "Creates/maintains Tasks.md, manages dependencies, tracks progress"
agent_teams_add_member --name "architect" --role "Solution Architect" --description "Designs architecture, creates ERD, makes technical decisions"
agent_teams_add_member --name "backend_dev" --role "Backend Developer" --description "Builds APIs, manages database, implements business logic"
agent_teams_add_member --name "frontend_dev" --role "Frontend Developer" --description "Implements UI/UX, follows design guidelines"
agent_teams_add_member --name "qa" --role "QA Tester" --description "Tests features, finds bugs, ensures quality"

# Add optional specialists as needed:
# agent_teams_add_member --name "devops" --role "DevOps" --description "CI/CD, deployment, infrastructure"
# agent_teams_add_member --name "ba" --role "Business Analyst" --description "Requirements, user stories, PRD"
```

---

### Step 3: Load Documentation into Memory

**If documentation files exist:** Load them into memory:

```bash
# Save each documentation file to memory
memory_save --content "$(cat PRD.md)" --namespace project --keywords "PRD,requirements,features,user-stories" --status suggested
memory_save --content "$(cat Architecture.md)" --namespace project --keywords "Architecture,tech-stack,ERD,data-flow" --status suggested
memory_save --content "$(cat Decisions.md)" --namespace project --keywords "Decisions,technical-choices,rationale" --status suggested
memory_save --content "$(cat Tasks.md)" --namespace project --keywords "Tasks,progress,dependencies,assignees" --status suggested
memory_save --content "$(cat AGENTS.md)" --namespace project --keywords "AGENTS,instructions,workflow,rules" --status suggested
```

**If documentation files DON'T exist:** The agent team will create them (see Scenario B in Initial Setup).

---

### Step 4: Create Workflow (In-Chat)

```bash
# Create DAG workflow
create_flow --name "$DSH_PROJECT_NAME_dev" --description "Development workflow for $DSH_PROJECT_NAME"

# Create initial tasks (customize for your project)
create_task --flow_id "$DSH_PROJECT_NAME_dev" --title "Analyze Requirements & Create PRD" --description "Review features, create detailed PRD.md with user stories" --assignee "pm" --acceptance_criteria "PRD.md approved with all features covered"
create_task --flow_id "$DSH_PROJECT_NAME_dev" --title "Design System Architecture" --description "Create Architecture.md with tech stack, data flow, ERD diagram" --assignee "architect" --dependencies "task_1" --acceptance_criteria "Architecture.md includes ERD, tech decisions, API design"
create_task --flow_id "$DSH_PROJECT_NAME_dev" --title "Implement Core Features" --description "Build backend APIs and frontend for key features" --assignee "backend_dev" --dependencies "task_2" --acceptance_criteria "All features implemented per Architecture.md"
create_task --flow_id "$DSH_PROJECT_NAME_dev" --title "Test & Quality Assurance" --description "Run test suite, verify all features work correctly" --assignee "qa" --dependencies "task_3" --acceptance_criteria "All tests pass, no critical bugs"
```

---

## 🔄 Multi-Project Memory Isolation

**Each project has completely separate memory:**

```
~/projects/
├── project_a/          ← Workspace 1
│   └── .dsh/storages/  ← Memories for project_a only
├── project_b/          ← Workspace 2
│   └── .dsh/storages/  ← Memories for project_b only
└── project_c/          ← Workspace 3
    └── .dsh/storages/  ← Memories for project_c only
```

**How it works:**
- `--namespace project` = "current workspace's memories only"
- `dsh-project <name>` switches workspace AND loads `.dshrc`
- Memory is automatically isolated per project directory

**Switching projects:**
```bash
dsh-project project_a    # Loads project_a memory
dsh-project project_b    # Loads project_b memory (project_a memory hidden)
```

---

## ✅ Verification Checklist

### Phase 0 (One-time + Per Project)
- [ ] Define project values (placeholders table)
- [ ] Create `.dshrc` in project root with YOUR values
- [ ] Create `~/.dsh/load-project.sh` (once per machine)
- [ ] Add aliases to `~/.bashrc` (once per machine)
- [ ] Run `dsh-project <project_name>` to load config
- [ ] Verify `$DSH_PROJECT_NAME`, `$DSH_PROJECT_TYPE`, `$DSH_TEAM_NAME` are set

### Web GUI Workflow
- [ ] Run `memory_save` for project identity (soul layer)
- [ ] Run `memory_save` for tech stack (project layer)
- [ ] Run `memory_save` for project type/team
- [ ] Run `agent_teams_create` to create team
- [ ] Run `agent_teams_add_member` for each role
- [ ] Load documentation files with `memory_save` (if exist)
- [ ] Run `create_flow` for workflow
- [ ] Run `create_task` for initial tasks with dependencies
- [ ] Run `agent_teams_status` to verify team

---

## 📋 Quick Reference Cheatsheet

### In-Chat Commands (Web GUI)

| Action | Command | Description |
|--------|---------|-------------|
| **Save Memory** | `memory_save --content "text" --namespace project --keywords "tag1,tag2" --status suggested` | Save fact/lesson/topic/rule to memory |
| **List Memories** | `memory_list --namespace project --status suggested` | List all project memories |
| **Search Memories** | `memory_search --query "keyword" --namespace project` | Search project memories |
| **Confirm Memory** | `memory_confirm --id "memory-id"` | Approve suggested memory (human only) |
| **Update Memory** | `memory_update --id "memory-id" --content "new text"` | Update existing memory |
| **Delete Memory** | `memory_forget --id "memory-id"` | Delete a memory |
| **Create Team** | `agent_teams_create --name "team-name" --description "purpose"` | Create agent team |
| **Add Member** | `agent_teams_add_member --name "member" --role "role" --description "desc"` | Add agent to team |
| **Create Task** | `agent_teams_create_task --subject "title" --description "details" --assignee "member"` | Create task in team |
| **Check Status** | `agent_teams_status` | Show team and task status |
| **Create Flow** | `create_flow --name "flow-name" --description "purpose"` | Create workflow DAG |
| **Create Task (Flow)** | `create_task --flow_id "flow" --title "task" --description "details" --assignee "member"` | Create task in flow |
| **Submit Handoff** | `submit_handoff --task_id "from" --to_task_id "to" --document "context"` | Pass context between tasks |
| **List Tasks** | `list_tasks --scope inbox` | List tasks assigned to you |

### Memory Layers (In-Chat)

| Layer | Namespace | Purpose | Example Keywords |
|-------|-----------|---------|------------------|
| soul | `project` | Project identity and essence | `identity,brand,vision` |
| user | `project` | User-specific context | `preferences,session,user` |
| project | `project` | Project-wide facts and anchors | `tech-stack,architecture,team` |
| fact | `project` | Individual data points | `config,api-endpoint,schema` |
| lesson | `project` | Learned best practices | `best-practice,anti-pattern,workflow` |
| topic | `project` | Subject matter topics | `feature-area,domain,module` |
| rules | `project` | Behavioral constraints | `constraint,pattern,enforcement` |

### Documentation Files

| File | Primary Owner | Key Content |
|------|---------------|-------------|
| PRD.md | BA | What, goals, features, user stories, metrics |
| Architecture.md | Architect | Tech stack, data flow, ERD, component diagram |
| Decisions.md | Senior Dev | Why choices were made, alternatives considered |
| Tasks.md | PM | What tasks, status, assignee, dependencies, acceptance criteria |
| AGENTS.md | CEO | How agents should behave, system prompts, workflow rules |

---

## 📝 Appendix: Placeholder Quick Reference

### Use These Placeholders (Never Real Values)

| Placeholder | Meaning | Example |
|-------------|---------|---------|
| `[PROJECT_NAME]` | Name of your project | `my_project`, `task_manager`, `ecommerce_platform` |
| `[PROJECT_TYPE]` | Type of software project | `desktop application`, `web API`, `mobile app`, `SaaS platform` |
| `[KEY_FEATURES]` | Main features of the project | `user, role, auth, ...` |
| `[TEAM_NAME]` | Name of your agent team | `Team Alpha`, `Software Group`, `Development Squad` |
| `[TECH_STACK]` | Tech stack summary | `Laravel + Vue/Nuxt + SQLite + TypeScript`, `React + Node.js + PostgreSQL` |
| `[PROJECT_SCOPE]` | Scope description | `single-station, offline-first, desktop+web`, `multi-tenant, cloud` |
| `[DSH_CEO_NAME]` | Your name as CEO | `John Doe`, `Jane Smith` |
| `[ROLE]` | Agent role | `PM`, `BA`, `Architect`, `Developer`, `QA` |
| `[TASK_ID]` | Unique task identifier | `TASK_001`, `FEATURE_123`, `BUG_456` |
| `[MODEL]` | AI model name | `nemotron`, `gpt-4`, `claude-3`, `gemini` |

### Never Use These in the Document
- Specific technology names (React, Node.js, Python, etc.)
- Specific project features (inventory management, POS, etc.)
- Specific business domains (ecommerce, healthcare, etc.)
- Company names or real project identifiers in the main body (use placeholders)

---

## ⚠️ Important Notes

1. **Memory is per-workspace** — Each project directory has isolated memory via `.dsh/storages/`
2. **`.dshrc` is per-project** — Never share/commit it; each project creates its own
3. **`load-project.sh` is global** — Created once, finds `.dshrc` from current directory
4. **Web GUI tools don't need plugins** — `memory_save`, `agent_teams_create`, etc. work directly
5. **Environment variables flow** — `.dshrc` → `dsh-load` → available in all subsequent commands
6. **Never start without docs** — Approved PRD.md, Architecture.md, Decisions.md, Tasks.md, AGENTS.md required

---

*This guide is project-agnostic. Replace all placeholders with your actual project values before use. The workflow works for ANY software project type without modification to the core process.*