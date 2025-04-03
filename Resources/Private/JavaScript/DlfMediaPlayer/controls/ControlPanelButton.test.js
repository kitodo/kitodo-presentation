/**
 * @jest-environment jsdom
 */

// @ts-check

import { describe, expect, test } from '@jest/globals';
import Environment from 'lib/Environment';
import { e } from 'lib/util';
import { action } from 'DlfMediaPlayer/lib/action';
import ControlPanelButton from 'DlfMediaPlayer/controls/ControlPanelButton';
import { createShakaPlayer } from 'DlfMediaPlayer/controls/test-util';

describe('ControlPanelButton', () => {
  const shk = createShakaPlayer();
  const env = new Environment();
  env.setLang({
    locale: 'en_US',
    twoLetterIsoCode: 'en',
    phrases: {
      'button.tooltip': "Do it now",
    },
  })

  test('basic', () => {
    let clicked = 0;
    const buttonContainer = document.createElement('div');
    const element = e('button', {
      className: `material-icons-round`,
    }, ['info']);
    element.setAttribute('data-t-title', 'button.tooltip');
    const button = new ControlPanelButton(buttonContainer, shk.controls, env, {
      element,
      onClickAction: action({
        execute: () => {
          clicked++;
        },
      }),
    });
    expect(button).toBeInstanceOf(ControlPanelButton);
    expect(button.eventManager).toBeDefined();
    const domButton = buttonContainer.querySelector('button');
    expect(domButton?.className.startsWith("material-icons-round")).toBe(true);
    expect(domButton?.textContent).toBe("info");
    expect(domButton?.ariaLabel).toBe("Do it now");
    domButton?.click();
    expect(clicked).toBe(1);
  });

  test('allows to omit title', () => {
    const buttonContainer = document.createElement('div');
    const button = new ControlPanelButton(buttonContainer, shk.controls, env);
    const domButton = buttonContainer.querySelector('button');
    expect(button).toBeInstanceOf(ControlPanelButton);
    expect(button.eventManager).toBeDefined();
    expect(domButton?.ariaLabel).toBe("");
  });
});
