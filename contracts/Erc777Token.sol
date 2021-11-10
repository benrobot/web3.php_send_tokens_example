// SPDX-License-Identifier: MIT
pragma solidity ^0.8.4;

import "@openzeppelin/contracts-upgradeable/token/ERC777/ERC777Upgradeable.sol";
import "@openzeppelin/contracts-upgradeable/security/PausableUpgradeable.sol";
import "@openzeppelin/contracts-upgradeable/access/AccessControlUpgradeable.sol";

contract Erc777Token is ERC777Upgradeable, PausableUpgradeable, AccessControlUpgradeable {
    bytes32 public constant PAUSER_ROLE = keccak256("PAUSER_ROLE");
    bytes32 public constant MINTER_ROLE = keccak256("MINTER_ROLE");

    uint private constant INITIAL_SUPPLY = 1000000000_000000000000000000;
    string private constant TOKEN_NAME = "MyErc777";
    string private constant TOKEN_SYMBOL = "ME777";

    function initialize(
        address[] memory defaultOperators
    ) public initializer {
        __ERC777_init(TOKEN_NAME, TOKEN_SYMBOL, defaultOperators);
        __Pausable_init();
        __AccessControl_init();

        _setupRole(DEFAULT_ADMIN_ROLE, msg.sender);
        _setupRole(PAUSER_ROLE, msg.sender);
        _setupRole(MINTER_ROLE, msg.sender);

        _mint(msg.sender, INITIAL_SUPPLY, "", "");
    }

    function pause() public onlyRole(PAUSER_ROLE) {
        super._pause();
    }

    function unpause() public onlyRole(PAUSER_ROLE) {
        super._unpause();
    }

    function mint(
        address account,
        uint256 amount,
        bytes memory userData,
        bytes memory operatorData
    ) public onlyRole(MINTER_ROLE) {
        super._mint(account, amount, userData, operatorData, true);
    }

    function mint(
        address account,
        uint256 amount,
        bytes memory userData,
        bytes memory operatorData,
        bool requireReceptionAck
    ) public onlyRole(MINTER_ROLE) {
        super._mint(account, amount, userData, operatorData, requireReceptionAck);
    }

    function _beforeTokenTransfer(
        address operator,
        address from,
        address to,
        uint256 amount
    ) internal whenNotPaused override {
        super._beforeTokenTransfer(operator, from, to, amount);
    }
}
