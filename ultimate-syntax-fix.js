import fs from 'fs';
import { globSync } from 'glob';

const colors = {
  reset: '\x1b[0m',
  green: '\x1b[32m',
  yellow: '\x1b[33m',
  blue: '\x1b[34m',
  red: '\x1b[31m',
};

function log(message, color = 'reset') {
  console.log(`${colors[color]}${message}${colors.reset}`);
}

/**
 * Ultimate fix for remaining edge cases
 */
function ultimateFix(content) {
  const lines = content.split('\n');
  const result = [];

  for (let i = 0; i < lines.length; i++) {
    const line = lines[i];
    const nextLine = i + 1 < lines.length ? lines[i + 1] : '';
    const nextNextLine = i + 2 < lines.length ? lines[i + 2] : '';

    // Pattern: closing }); of router.get() directly followed by const resetFilters
    // This means search() function is missing its closing };
    if (/^\s*\}\);$/.test(line) && /^\s*const resetFilters/.test(nextLine)) {
      result.push(line);
      result.push('};');
      result.push('');
      continue;
    }

    // Pattern: resetFilters function without closing brace before final };
    if (/router\.get\([^)]*\);$/.test(line) && /^\s*};\s*};$/.test(nextLine)) {
      result.push(line);
      // Next line has };};, should be just };
      result.push('};');
      i++; // Skip the };  }; line
      continue;
    }

    // Pattern: Duplicate };  }; on same line or next lines
    if (/^\s*};\s*};$/.test(line)) {
      result.push('};');
      continue;
    }

    // Pattern: Empty line followed by };  };
    if (/^$/.test(line) && /^\s*};\s*};$/.test(nextLine)) {
      result.push(line);
      result.push('};');
      i++; // Skip the duplicate
      continue;
    }

    // Pattern: Line with }); followed by empty line and then };  };
    if (/\}\);$/.test(line) && /^$/.test(nextLine) && /^\s*};\s*};$/.test(nextNextLine)) {
      result.push(line);
      result.push('');
      result.push('};');
      i += 2; // Skip both empty line and duplicate braces
      continue;
    }

    result.push(line);
  }

  // Final pass: cleanup any remaining patterns
  let finalContent = result.join('\n');

  // Remove lines that are just duplicate };
  finalContent = finalContent.replace(/};\s*\n\s*};(\s*\n|$)/g, '};$1');

  return finalContent;
}

function processFile(filePath) {
  try {
    let content = fs.readFileSync(filePath, 'utf-8');
    const originalContent = content;

    content = ultimateFix(content);

    if (content === originalContent) {
      return { status: 'ok', message: 'OK' };
    }

    fs.writeFileSync(filePath, content, 'utf-8');
    return { status: 'fixed', message: 'Fixed' };
  } catch (error) {
    return { status: 'error', message: error.message };
  }
}

function main() {
  log('\n=== Ultimate Syntax Fix ===\n', 'blue');

  const pattern = 'Modules/*/Resources/js/Pages/*/Index.vue';
  const files = globSync(pattern);

  if (files.length === 0) {
    log('No files found', 'yellow');
    return;
  }

  log(`Checking ${files.length} Index.vue files\n`, 'blue');

  let fixed = 0;
  let ok = 0;
  let errors = 0;

  files.forEach((filePath) => {
    const result = processFile(filePath);
    const relativePath = filePath.replace(/\\/g, '/');

    switch (result.status) {
      case 'fixed':
        log(`✓ ${relativePath}`, 'green');
        fixed++;
        break;
      case 'ok':
        ok++;
        break;
      case 'error':
        log(`✗ ${relativePath} - ${result.message}`, 'red');
        errors++;
        break;
    }
  });

  log(`\n=== Summary ===`, 'blue');
  log(`Fixed: ${fixed}`, 'green');
  log(`OK: ${ok}`, 'blue');
  log(`Errors: ${errors}\n`, errors > 0 ? 'red' : 'blue');
}

main();
